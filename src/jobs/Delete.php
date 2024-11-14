<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;

class Delete extends BaseJob
{
	public ?string $indexHandle = null;

	public string|int $identifier;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['indexName'])) {
			// Index name is deprecated
			$config['indexHandle'] = $config['indexName'];
			unset($config['indexName']);
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
		$indices->each(function ($index, $i) use ($queue, $totalIndices): void {
			Plugin::getInstance()->sync->delete($index, $this->identifier);
			$this->setProgress($queue, $i / $totalIndices);
		});
	}

	protected function defaultDescription(): ?string
	{
		$description = "Deleting {$this->identifier} from";
		if ($this->indexHandle === null) {
			return "{$description} all indices";
		}

		return "{$description} {$this->indexHandle}";
	}
}
