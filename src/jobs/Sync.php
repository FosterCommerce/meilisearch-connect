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

		$maxLevel = Plugin::getInstance()->settings->maxDependencyRecursionLevel;
		if ($this->dependencyRecursionLevel >= $maxLevel) {
			$this->setProgress($queue, 1);
			Craft::info("Maximum recursion level reached after syncing {$this->sourceHandle} for index {$this->indexHandle}. Stopping dependency sync.", 'meilisearch-connect');
			return;
		}

		// Find dependent sources and sync them too
		$indices->each(function (Index $index): void {
			$currentSource = null;
			$syncedSourcesQuery = Source::find()
				->where([
					'indexHandle' => $index->handle,
				]);

			if ($this->sourceHandle !== null) {
				$currentSource = Source::get($index->handle, (string) $this->sourceHandle);
				$syncedSourcesQuery->andWhere([
					'handle' => (string) $this->sourceHandle,
				]);
			}

			/** @var Source $batchQueryResult */
			foreach ($syncedSourcesQuery->each() as $batchQueryResult) {
				foreach ($batchQueryResult->getParentSources()->each() as $parentSource) {
					// Prevent immediately obvious cyclic dependencies
					/** @var Source $parentSource */
					if ($currentSource?->detectCyclicDependency($parentSource->id) === true) {
						Craft::warning("Cyclic dependency detected between {$currentSource->handle} and {$parentSource->handle} in index {$parentSource->indexHandle}. Skipping dependency sync.", 'meilisearch-connect');
						continue;
					}

					Queue::push(new Sync([
						'indexHandle' => $parentSource->indexHandle,
						'sourceHandle' => $parentSource->handle,
						'dependencyRecursionLevel' => $this->dependencyRecursionLevel + 1,
					]));
				}
			}
		});

		$this->setProgress($queue, 1);
	}

	protected function defaultDescription(): ?string
	{
		if ($this->sourceHandle === null && $this->indexHandle === null) {
			return 'Sync all indices';
		}

		if ($this->sourceHandle === null) {
			return "Sync index {$this->indexHandle}";
		}

		$indexes = Plugin::getInstance()->settings->getIndices($this->indexHandle);

		if ($indexes instanceof Index) {
			$indexes = [$indexes];
		}

		return 'Sync ' . collect($indexes)
			->map(fn (Index $index): string => "'{$index->getDocumentName((string) $this->sourceHandle)}' ({$index->handle})")
			->join(', ');
	}
}
