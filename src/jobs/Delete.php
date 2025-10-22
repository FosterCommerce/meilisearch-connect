<?php

namespace fostercommerce\meilisearch\jobs;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;
use fostercommerce\meilisearch\records\Source;

class Delete extends BaseJob
{
	public ?string $indexHandle = null;

	public string|int $sourceHandle;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['indexName'])) {
			Craft::$app->getDeprecator()->log('indexName', '`Delete` job `indexName` property has been deprecated. Use `indexHandle` instead.');
			$config['indexHandle'] = $config['indexName'];
			unset($config['indexName']);
		}

		if (isset($config['identifier'])) {
			Craft::$app->getDeprecator()->log('identifier', '`Delete` job `identifier` property has been deprecated. Use `sourceHandle` instead.');
			$config['sourceHandle'] = $config['identifier'];
			unset($config['identifier']);
		}

		parent::__construct($config);
	}

	public function execute($queue): void
	{
		$indices = Plugin::getInstance()->settings->getIndices($this->indexHandle);

		if ($indices instanceof Index) {
			$indices = [$indices];
		}

		$indices = collect($indices);

		$totalIndices = $indices->count();

		// Find dependent sources and sync them
		$indices->each(function (Index $index): void {
			$source = Source::findOne([
				'indexHandle' => $index->handle,
				'handle' => (string) $this->sourceHandle,
			]);

			if ($source === null) {
				return;
			}

			/** @var Source $batchQueryResult */
			foreach ($source->getChildSources()->each() as $batchQueryResult) {
				Queue::push(new Sync([
					'indexHandle' => $this->indexHandle,
					'sourceHandle' => $batchQueryResult->handle,
				]));
			}
		});

		$indices->each(function ($index, $i) use ($queue, $totalIndices): void {
			Plugin::getInstance()->sync->delete($index, (string) $this->sourceHandle);
			$this->setProgress($queue, $i / $totalIndices);
		});
	}

	protected function defaultDescription(): ?string
	{
		$description = "Deleting {$this->sourceHandle} from";
		if ($this->indexHandle === null) {
			return "{$description} all indices";
		}

		return "{$description} {$this->indexHandle}";
	}
}
