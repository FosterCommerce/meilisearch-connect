<?php

namespace fostercommerce\meilisearch\services;

use Craft;
use fostercommerce\meilisearch\events\SyncEvent;
use fostercommerce\meilisearch\helpers\DocumentList;
use fostercommerce\meilisearch\models\Index;
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
		$this->runWithIndexMutex($index, function () use (&$index): void {
			$this->meiliClient
				->index($index->indexId)
				->deleteAllDocuments();

			TrackedDocument::deleteAll([
				'indexHandle' => $index->handle,
			]);
		});
	}

	public function delete(Index $index, string|int $sourceId): void
	{
		$this->runWithIndexMutex($index, function () use (&$index, &$sourceId): void {
			$trackedDocuments = TrackedDocument::findAll([
				'indexHandle' => $index->handle,
				'sourceId' => $sourceId,
			]);

			foreach ($trackedDocuments as $trackedDocument) {
				$this->meiliClient->index($index->indexId)->deleteDocument($trackedDocument->documentId);
			}

			TrackedDocument::deleteAll([
				'indexHandle' => $index->handle,
				'sourceId' => $sourceId,
			]);
		});
	}

	/**
	 * @param string|int|null $sourceId The value used to identify a single item in the index
	 * @return Generator<int, int>
	 */
	public function sync(Index $index, null|string|int $sourceId): Generator
	{
		$generator = $this->runWithIndexMutex($index, function () use (&$index, &$sourceId): Generator {
			$discoveredSourceIds = [];

			/** @var DocumentList[] $documentLists */
			foreach ($index->execFetchFn($sourceId) as $documentLists) {
				$event = new SyncEvent([
					'documentLists' => $documentLists,
					'meiliClient' => $this->meiliClient,
				]);

				if ($this->hasEventHandlers(self::EVENT_BEFORE_SYNC_CHUNK)) {
					$this->trigger(self::EVENT_BEFORE_SYNC_CHUNK, $event);
				}

				$documentLists = $event->documentLists;
				$size = 0;
				$documentBatches = [];

				foreach ($documentLists as $documentList) {
					// When discovering a new source Entry ID, mark all
					// existing tracked documents as pending for deletion.
					if (! in_array($documentList->sourceId, $discoveredSourceIds, true)) {
						$discoveredSourceIds[] = $documentList->sourceId;

						TrackedDocument::updateAll([
							'pendingDeletion' => true,
						], [
							'indexHandle' => $index->handle,
							'sourceId' => $documentList->sourceId,
						]);
					}

					$size += count($documentList->documents);
					$documentBatches[] = $documentList->documents;

					// Exclude all tracked documents that have been added to/updated in the index
					// from deletion
					foreach ($documentList->documents as $document) {
						$trackedDocumentIdentifier = [
							'indexHandle' => $index->handle,
							'sourceId' => $documentList->sourceId,
							'documentId' => $document[$index->getSettings()->primaryKey],
						];

						$trackedDocumentQuery = TrackedDocument::find()->where($trackedDocumentIdentifier);
						if ($trackedDocumentQuery->exists()) {
							/** @var TrackedDocument $document */
							$document = $trackedDocumentQuery->one();
							$document->pendingDeletion = false;
							$document->save();
						} else {
							(new TrackedDocument($trackedDocumentIdentifier))->save();
						}
					}
				}

				$this->meiliClient
					->index($index->indexId)
					->addDocuments(array_merge(...$documentBatches), $index->getSettings()->primaryKey);

				yield $size;

				if ($this->hasEventHandlers(self::EVENT_AFTER_SYNC_CHUNK)) {
					$this->trigger(self::EVENT_AFTER_SYNC_CHUNK, $event);
				}
			}

			// Purge documents that should no longer exist
			$documentsToBeDeleted = TrackedDocument::findAll([
				'indexHandle' => $index->handle,
				'pendingDeletion' => true,
			]);

			foreach ($documentsToBeDeleted as $documentToBeDeleted) {
				$this->meiliClient->index($index->indexId)->deleteDocument($documentToBeDeleted->documentId);
			}

			TrackedDocument::deleteAll([
				'indexHandle' => $index->handle,
				'pendingDeletion' => true,
			]);
		});

		foreach ($generator as $item) {
			yield $item;
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

	/**
	 * @template TReturn of mixed
	 * @param callable(): TReturn $callable
	 * @return TReturn
	 */
	private function runWithIndexMutex(Index $index, callable $callable): mixed
	{
		// Make sure there's only one job tracking documents simultaneously.
		$mutexName = $index->getDocumentTrackingMutexName();
		$mutexAcquired = Craft::$app->mutex->acquire($mutexName, 1800);

		if (! $mutexAcquired) {
			throw new \RuntimeException("Unable to acquire document tracking mutex for index {$index->handle}");
		}

		$returnValue = $callable();

		Craft::$app->mutex->release($mutexName);

		return $returnValue;
	}
}
