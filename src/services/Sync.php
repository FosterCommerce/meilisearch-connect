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

		/** @var array{pkgVersion: string} $versionResponse */
		$versionResponse = $this->meiliClient->version();
		$version = $versionResponse['pkgVersion'];

		$meilisearchIndex = $this->meiliClient->index($index->indexId);
		$indexSettings = $index->getSettings();

		if ($indexSettings->sortableAttributes !== null) {
			$meilisearchIndex->updateSortableAttributes($indexSettings->sortableAttributes);
		} else {
			$meilisearchIndex->resetSortableAttributes();
		}

		if ($indexSettings->dictionary !== null) {
			$meilisearchIndex->updateDictionary($indexSettings->dictionary);
		} else {
			$meilisearchIndex->resetDictionary();
		}

		if ($indexSettings->displayedAttributes !== null) {
			$meilisearchIndex->updateDisplayedAttributes($indexSettings->displayedAttributes);
		} else {
			$meilisearchIndex->resetDisplayedAttributes();
		}

		if ($indexSettings->distinctAttribute !== null) {
			$meilisearchIndex->updateDistinctAttribute($indexSettings->distinctAttribute);
		} else {
			$meilisearchIndex->resetDistinctAttribute();
		}

		if ($indexSettings->faceting !== null) {
			$meilisearchIndex->updateFaceting($indexSettings->faceting);
		} else {
			$meilisearchIndex->resetFaceting();
		}

		if ($indexSettings->filterableAttributes !== null) {
			$meilisearchIndex->updateFilterableAttributes($indexSettings->filterableAttributes);
		} else {
			$meilisearchIndex->resetFilterableAttributes();
		}

		if ($indexSettings->pagination !== null) {
			$meilisearchIndex->updatePagination($indexSettings->pagination);
		} else {
			$meilisearchIndex->resetPagination();
		}

		if ($indexSettings->proximityPrecision !== null) {
			$meilisearchIndex->updateProximityPrecision($indexSettings->proximityPrecision);
		} else {
			$meilisearchIndex->resetProximityPrecision();
		}

		if ($indexSettings->ranking !== null) {
			$meilisearchIndex->updateRankingRules($indexSettings->ranking);
		} else {
			$meilisearchIndex->resetRankingRules();
		}

		if ($indexSettings->searchableAttributes !== null) {
			$meilisearchIndex->updateSearchableAttributes($indexSettings->searchableAttributes);
		} else {
			$meilisearchIndex->resetSearchableAttributes();
		}

		if ($indexSettings->searchCutoffMs !== null) {
			$meilisearchIndex->updateSearchCutoffMs($indexSettings->searchCutoffMs);
		} else {
			$meilisearchIndex->resetSearchCutoffMs();
		}

		if ($indexSettings->separatorTokens !== null) {
			$meilisearchIndex->updateSeparatorTokens($indexSettings->separatorTokens);
		} else {
			$meilisearchIndex->resetSeparatorTokens();
		}

		if ($indexSettings->nonSeparatorTokens !== null) {
			$meilisearchIndex->updateNonSeparatorTokens($indexSettings->nonSeparatorTokens);
		} else {
			$meilisearchIndex->resetNonSeparatorTokens();
		}

		if ($indexSettings->stopWords !== null) {
			$meilisearchIndex->updateStopWords($indexSettings->stopWords);
		} else {
			$meilisearchIndex->resetStopWords();
		}

		if ($indexSettings->synonyms !== null) {
			$meilisearchIndex->updateSynonyms($indexSettings->synonyms);
		} else {
			$meilisearchIndex->resetSynonyms();
		}

		if ($indexSettings->typoTolerance !== null) {
			$meilisearchIndex->updateTypoTolerance($indexSettings->typoTolerance);
		} else {
			$meilisearchIndex->resetTypoTolerance();
		}

		if ($indexSettings->embedders !== null) {
			$meilisearchIndex->updateEmbedders($indexSettings->embedders);
		} else {
			$meilisearchIndex->resetEmbedders();
		}

		if (version_compare($version, '1.12.0', '>=')) {
			if ($indexSettings->facetSearch !== null) {
				$meilisearchIndex->updateFacetSearch($indexSettings->facetSearch);
			} else {
				$meilisearchIndex->resetFacetSearch();
			}

			if ($indexSettings->prefixSearch !== null) {
				$meilisearchIndex->updatePrefixSearch($indexSettings->prefixSearch);
			} else {
				$meilisearchIndex->resetPrefixSearch();
			}
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
