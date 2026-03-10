<?php

namespace fostercommerce\meilisearch\services;

use Craft;
use fostercommerce\meilisearch\events\SyncEvent;
use fostercommerce\meilisearch\helpers\DocumentList;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\records\Source;
use fostercommerce\meilisearch\records\SourceDependency;
use fostercommerce\meilisearch\records\TrackedDocument;
use Generator;
use Meilisearch\Exceptions\TimeOutException;
use yii\base\Component;
use yii\base\Exception;

class Sync extends Component
{
	use Meili;

	/**
	 * Event::on(
	 *   Sync::class,
	 *   Sync::EVENT_BEFORE_SYNC_CHUNK,
	 *   function (SyncEvent $event) {
	 *       // Do something with the document list before it's synced.
	 *       // $event->documentList->documents = ;
	 *   }
	 * );
	 *
	 * @var string
	 */
	public const EVENT_BEFORE_SYNC_CHUNK = 'beforeSyncChunk';

	/**
	 * Event::on(
	 *   Sync::class,
	 *   Sync::EVENT_AFTER_SYNC_CHUNK,
	 *   function (SyncEvent $event) {
	 *       // Do something with $event->documentList after it's synced.
	 *   }
	 * );
	 *
	 * @var string
	 */
	public const EVENT_AFTER_SYNC_CHUNK = 'afterSyncChunk';

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
		if ($index->isSearchOnly()) {
			return;
		}

		$createIndexRes = $this->meiliClient->createIndex($index->indexId);
		$this->meiliClient->waitForTask($createIndexRes['taskUid']);

		/** @var array{pkgVersion: string} $versionResponse */
		$versionResponse = $this->meiliClient->version();
		$version = $versionResponse['pkgVersion'];

		$meilisearchIndex = $this->meiliClient->index($index->indexId);
		$indexSettings = $index->getIndexSettings();

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
		if ($index->isSearchOnly()) {
			return;
		}

		Source::getDb()->transaction(function () use ($index): void {
			$this->meiliClient
				->index($index->indexId)
				->deleteAllDocuments();

			Source::deleteAll([
				'indexHandle' => $index->handle,
			]);
		});
	}

	public function delete(Index $index, string $sourceHandle): void
	{
		if ($index->isSearchOnly()) {
			return;
		}

		Source::getDb()->transaction(function () use ($sourceHandle, $index): void {
			$source = Source::get($index->handle, $sourceHandle);

			if (! $source instanceof Source) {
				return;
			}

			/** @var TrackedDocument $batchQueryResult */
			foreach ($source->getTrackedDocuments()->each() as $batchQueryResult) {
				$this->meiliClient->index($index->indexId)->deleteDocument($batchQueryResult->documentId);
			}

			$source->delete();
		});
	}

	/**
	 * @param null|string $sourceHandle The value used to identify a single item in the index
	 * @return Generator<int>
	 */
	public function sync(Index $index, null|string $sourceHandle): Generator
	{
		if ($index->isSearchOnly()) {
			return;
		}

		/** @var DocumentList[] $documentLists */
		foreach ($index->execFetchFn($sourceHandle) as $documentLists) {
			$event = new SyncEvent([
				'documentLists' => $documentLists,
				'meiliClient' => $this->meiliClient,
			]);

			if ($this->hasEventHandlers(self::EVENT_BEFORE_SYNC_CHUNK)) {
				$this->trigger(self::EVENT_BEFORE_SYNC_CHUNK, $event);
			}

			$documentLists = $event->documentLists;
			$documents = [];
			$removedDocumentIds = [];

			foreach ($documentLists as $documentList) {
				$source = Source::get($index->handle, $documentList->sourceHandle, true);

				// Fetch old document IDs before clearing so we can diff later
				/** @var string[] $oldDocumentIds */
				$oldDocumentIds = TrackedDocument::find()
					->select('documentId')
					->where([
						'sourceId' => $source->id,
					])
					->column();

				TrackedDocument::deleteAll([
					'sourceId' => $source->id,
				]);

				SourceDependency::deleteAll([
					'parentSourceId' => $source->id,
				]);

				$documents = [...$documents, ...$documentList->documents];

				// Insert new tracked documents
				$newDocumentIds = [];
				foreach ($documentList->documents as $document) {
					$documentId = $document[$index->getIndexSettings()->primaryKey];
					$newDocumentIds[] = $documentId;

					(new TrackedDocument([
						'sourceId' => $source->id,
						'documentId' => $documentId,
					]))->save();
				}

				$removedDocumentIds = [...$removedDocumentIds, ...array_diff($oldDocumentIds, $newDocumentIds)];

				foreach ($documentList->dependentSourceHandles as $dependentSourceHandle) {
					(new SourceDependency([
						'sourceId' => Source::get($index->handle, $dependentSourceHandle, true)->id,
						'parentSourceId' => $source->id,
					]))->save();
				}
			}

			$this->meiliClient->index($index->indexId)->deleteDocuments($removedDocumentIds);
			$this->meiliClient
				->index($index->indexId)
				->addDocuments($documents, $index->getIndexSettings()->primaryKey);

			yield count($documents);

			if ($this->hasEventHandlers(self::EVENT_AFTER_SYNC_CHUNK)) {
				$this->trigger(self::EVENT_AFTER_SYNC_CHUNK, $event);
			}
		}
	}

	/**
	 * @return Generator<int, int>
	 * @throws Exception
	 * @throws TimeOutException
	 */
	public function refresh(Index $index): Generator
	{
		if ($index->isSearchOnly()) {
			return;
		}

		$swapIndex = clone $index;
		$postfix = Craft::$app->getSecurity()->generateRandomString(12);
		$swapIndex->indexId = "_swap_{$swapIndex->indexId}__{$postfix}";
		$swapIndex->handle = "_swap_{$swapIndex->handle}__{$postfix}";
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
			Source::deleteAll([
				'indexHandle' => $swapIndex->handle,
			]);

			throw new \RuntimeException($swapResult['error']['message'] ?? 'Failed to refresh index');
		}

		$meiliIndex->delete();

		Source::getDb()->transaction(function () use ($index, $swapIndex): void {
			Source::deleteAll([
				'indexHandle' => $index->handle,
			]);

			Source::updateAll(
				[
					'indexHandle' => $index->handle,
				],
				[
					'indexHandle' => $swapIndex->handle,
				],
			);
		});
	}

	public function getDocumentCount(Index $index): int
	{
		if ($index->isSearchOnly()) {
			return 0;
		}

		/** @var array{numberOfDocuments: int} $stats */
		$stats = $this->meiliClient->index($index->indexId)->stats();

		return $stats['numberOfDocuments'];
	}
}
