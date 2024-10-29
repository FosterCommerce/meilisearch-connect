<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\models\Settings;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Exceptions\TimeOutException;
use yii\base\Component;

class Sync extends Component
{
	use Meili;

	private ?Settings $_settings = null;

	public function init(): void
	{
		parent::init();

		$this->initMeiliClient();
		$this->_settings = Plugin::getInstance()->settings;
	}

	/**
	 * @throws TimeOutException
	 */
	public function syncSettings(?string $indexName = null): void
	{
		foreach ($this->getIndices($indexName) as $indexHandle => $indexConfig) {
			$createIndexRes = $this->meiliClient->createIndex($indexHandle);
			$this->meiliClient->waitForTask($createIndexRes['taskUid']);

			$index = $this->meiliClient->index($indexHandle);
			$indexSettings = $indexConfig->settings;

			$index->updateRankingRules($indexSettings->ranking);
			if ($indexSettings->searchableAttributes !== null) {
				$index->updateSearchableAttributes($indexSettings->searchableAttributes);
			}

			$index->updateFilterableAttributes($indexSettings->filterableAttributes);
			$index->updateSortableAttributes($indexSettings->sortableAttributes);
			$index->updateFaceting($indexSettings->faceting);
		}
	}

	public function syncIndices(?string $indexName = null, ?string $identifier = null): void
	{
		foreach ($this->getIndices($indexName) as $indexHandle => $indexConfig) {
			$index = $this->meiliClient->index($indexHandle);
			$fetch = $indexConfig->fetch;
			$index->addDocuments($fetch($identifier), $indexConfig->settings->primaryKey);
		}
	}

	public function refreshIndices(?string $indexName = null): void
	{
		foreach ($this->getIndices($indexName) as $indexHandle => $indexConfig) {
			$index = $this->meiliClient->index($indexHandle);
			$index->deleteAllDocuments();
			$fetch = $indexConfig->fetch;
			$index->addDocuments($fetch(null), $indexConfig->settings->primaryKey);
		}
	}

	public function delete(string $identifier, ?string $indexName = null): void
	{
		foreach (array_keys($this->getIndices($indexName)) as $indexHandle) {
			$this->meiliClient->index($indexHandle)->deleteDocument($identifier);
		}
	}

	/**
	 * @return array<string, Index>
	 */
	private function getIndices(?string $indexName = null): array
	{
		if ($indexName !== null) {
			return [
				$indexName => $this->_settings->indices[$indexName],
			];
		}

		return $this->_settings->indices;
	}
}
