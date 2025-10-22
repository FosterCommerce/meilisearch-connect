<?php

namespace fostercommerce\meilisearch\jobs;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;
use fostercommerce\meilisearch\records\Source;

class Sync extends BaseJob
{
	/**
	 * Sync jobs will create new sync jobs for dependencies recursively.
	 * This limit is to prevent spawning sync jobs indefinitely.
	 */
	public const MAX_DEPENDENCY_RECURSION_LEVEL = 8;

	public ?string $indexHandle = null;

	public null|string|int $sourceHandle = null;

	public int $dependencyRecursionLevel = 0;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['indexName'])) {
			Craft::$app->getDeprecator()->log('indexName', '`Sync` job `indexName` property has been deprecated. Use `indexHandle` instead.');
			$config['indexHandle'] = $config['indexName'];
			unset($config['indexName']);
		}

		if (isset($config['identifier'])) {
			Craft::$app->getDeprecator()->log('identifier', '`Sync` job `identifier` property has been deprecated. Use `sourceHandle` instead.');
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

		// Only get page count if we're not attempting to synchronize a specific item.
		$totalPages = $this->sourceHandle === null
			? $indices->reduce(static fn ($total, Index $index): int => $total + ($index->getPageCount() ?? 0), 0)
			: 0;

		$currentPage = 0;
		$sourceHandleString = $this->sourceHandle === null
			? null
			: (string) $this->sourceHandle;

		$indices->each(function (Index $index) use ($sourceHandleString, $queue, $totalPages, &$currentPage): void {
			foreach (Plugin::getInstance()->sync->sync($index, $sourceHandleString) as $chunkSize) {
				++$currentPage;

				if ($totalPages > 0) {
					$this->setProgress($queue, $currentPage / $totalPages);
				}
			}
		});

		// Find dependent sources and sync them too
		$indices->each(function (Index $index): void {
			$syncedSourcesQuery = Source::find()
				->where([
					'indexHandle' => $index->handle,
				]);

			if ($this->sourceHandle !== null) {
				$syncedSourcesQuery->andWhere([
					'handle' => (string) $this->sourceHandle,
				]);
			}

			/** @var Source $batchQueryResult */
			foreach ($syncedSourcesQuery->each() as $batchQueryResult) {
				/** @var Source $childSource */
				foreach ($batchQueryResult->getChildSources()->each() as $childSource) {
					if ($this->dependencyRecursionLevel === self::MAX_DEPENDENCY_RECURSION_LEVEL) {
						throw new \RuntimeException('Sync dependency recursion limit of ' . self::MAX_DEPENDENCY_RECURSION_LEVEL . ' levels reached.');
					}

					Queue::push(new Sync([
						'indexHandle' => $this->indexHandle,
						'sourceHandle' => $childSource->handle,
						'dependencyRecursionLevel' => $this->dependencyRecursionLevel + 1,
					]));
				}
			}
		});

		$this->setProgress($queue, 1);
	}

	protected function defaultDescription(): ?string
	{
		if ($this->sourceHandle !== null) {
			$description = "Sync {$this->sourceHandle} in";
			if ($this->indexHandle === null) {
				return "{$description} all indices";
			}

			return "{$description} {$this->indexHandle}";
		}

		if ($this->indexHandle === null) {
			return 'Sync all indices';
		}

		return "Sync {$this->indexHandle}";
	}
}
