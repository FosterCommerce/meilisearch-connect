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
		Source::getDb()->transaction(function () use (&$index): void {
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
		Source::getDb()->transaction(function () use (&$sourceHandle, &$index): void {
			$source = Source::findOne([
				'indexHandle' => $index->handle,
				'handle' => $sourceHandle,
			]);

			if ($source === null) {
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
		$transaction = Source::getDb()->beginTransaction();

		try {
			$discoveredSourceIds = [];

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
				$size = 0;
				$documentBatches = [];

				foreach ($documentLists as $documentList) {
					$source = Source::getOrCreate($index->handle, $documentList->sourceHandle);

					// When discovering a new source Entry ID, mark all
					// existing tracked documents as pending for deletion.
					if (! in_array($source->id, $discoveredSourceIds, true)) {
						$discoveredSourceIds[] = $source->id;

						TrackedDocument::updateAll([
							'pendingDeletion' => true,
						], [
							'sourceId' => $source->id,
						]);

						SourceDependency::deleteAll([
							'parentSourceId' => $source->id,
						]);
					}

					$size += count($documentList->documents);
					$documentBatches[] = $documentList->documents;

					// Exclude all tracked documents that have been added to/updated in the index
					// from deletion
					foreach ($documentList->documents as $document) {
						$trackedDocumentIdentifier = [
							'sourceId' => $source->id,
							'documentId' => $document[$index->getSettings()->primaryKey],
						];

						$existingTrackedDocument = TrackedDocument::findOne($trackedDocumentIdentifier);
						if ($existingTrackedDocument !== null) {
							$existingTrackedDocument->pendingDeletion = false;
							$existingTrackedDocument->save();
						} else {
							(new TrackedDocument($trackedDocumentIdentifier))->save();
						}
					}

					foreach ($documentList->dependentSourceHandles as $dependentSourceHandle) {
						(new SourceDependency([
							'sourceId' => Source::getOrCreate($index->handle, $dependentSourceHandle)->id,
							'parentSourceId' => $source->id,
						]))->save();
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
			$documentsToBeDeletedQuery = TrackedDocument::find()
				->joinWith('source')
				->where([
					'indexHandle' => $index->handle,
					'pendingDeletion' => true,
				]);

			/** @var TrackedDocument $batchQueryResult */
			foreach ($documentsToBeDeletedQuery->each() as $batchQueryResult) {
				$this->meiliClient->index($index->indexId)->deleteDocument($batchQueryResult->documentId);
				$batchQueryResult->delete();
			}

			$transaction->commit();
		} catch (\Throwable $throwable) {
			$transaction->rollBack();
			throw $throwable;
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
