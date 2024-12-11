<?php

namespace fostercommerce\meilisearch\services;

use Craft;
use fostercommerce\meilisearch\models\Index;
use Generator;
use Meilisearch\Exceptions\TimeOutException;
use yii\base\Component;
use yii\base\Exception;

class Sync extends Component
{
	use Meili;

	public function init(): void
	{
		parent::init();

		$this->initMeiliClient(useSearchKey: false);
	}

	/**
	 * @throws TimeOutException
	 */
	public function syncSettings(Index $index): void
	{
		$createIndexRes = $this->meiliClient->createIndex($index->indexId);
		$this->meiliClient->waitForTask($createIndexRes['taskUid']);

		$meilisearchIndex = $this->meiliClient->index($index->indexId);
		$indexSettings = $index->getSettings();

		$meilisearchIndex->updateRankingRules($indexSettings->ranking);
		if ($indexSettings->searchableAttributes !== null) {
			$meilisearchIndex->updateSearchableAttributes($indexSettings->searchableAttributes);
		} else {
			$meilisearchIndex->resetSearchableAttributes();
		}

		$meilisearchIndex->updateFilterableAttributes($indexSettings->filterableAttributes);
		$meilisearchIndex->updateSortableAttributes($indexSettings->sortableAttributes);

		if ($indexSettings->faceting !== null) {
			$meilisearchIndex->updateFaceting($indexSettings->faceting);
		} else {
			$meilisearchIndex->resetFaceting();
		}
	}

	public function flush(Index $index): void
	{
		$this->meiliClient
			->index($index->indexId)
			->deleteAllDocuments();
	}

	public function delete(Index $index, string|int $identifier): void
	{
		$this->meiliClient->index($index->indexId)->deleteDocument($identifier);
	}

	/**
	 * @param string|int|null $identifier The value used to identify a single item in the index
	 * @return Generator<int, int>
	 */
	public function sync(Index $index, null|string|int $identifier): Generator
	{
		foreach ($index->execFetchFn($identifier) as $chunk) {
			$size = count($chunk);

			// Remove any falsy values from the chunk of data.
			$chunk = array_values(array_filter($chunk));
			$this->meiliClient
				->index($index->indexId)
				->addDocuments($chunk, $index->getSettings()->primaryKey);

			yield $size;
		}
	}

	/**
	 * @return Generator<int, int>
	 * @throws Exception
	 * @throws TimeOutException
	 */
	public function refresh(Index $index): Generator
	{
		$swapIndex = clone $index;
		$postfix = Craft::$app->getSecurity()->generateRandomString(12);
		$swapIndex->indexId = "_swap_{$swapIndex->indexId}__{$postfix}";
		$this->syncSettings($swapIndex);

		foreach ($this->sync($swapIndex, null) as $size) {
			yield $size;
		}

		$meiliIndex = $this->meiliClient->index($swapIndex->indexId);
		$swapResult = $meiliIndex->swapIndexes([
			[
				'indexes' => [
					$index->indexId,
					$swapIndex->indexId,
				],
			],
		]);

		$swapResult = $this->meiliClient->waitForTask($swapResult['taskUid']);

		if ($swapResult['status'] !== 'succeeded') {
			throw new \RuntimeException($swapResult['error']['message'] ?? 'Failed to refresh index');
		}

		$meiliIndex->delete();
	}

	public function getDocumentCount(Index $index): int
	{
		/** @var array{numberOfDocuments: int} $stats */
		$stats = $this->meiliClient->index($index->indexId)->stats();

		return $stats['numberOfDocuments'];
	}
}
