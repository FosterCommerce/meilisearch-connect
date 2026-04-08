<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;
use yii\base\Component;

class Search extends Component
{
	use Meili;

	public function init(): void
	{
		$this->initMeiliClient(useSearchKey: true);
	}

	/**
	 * @param array<non-empty-string, mixed> $searchParams
	 * @param array<non-empty-string, mixed> $options
	 * @throws ApiException if the underlying Meilisearch request fails
	 */
	public function search(string $indexHandle, string $query, array $searchParams = [], array $options = []): SearchResult
	{
		/** @var Index $index */
		$index = Plugin::getInstance()->settings->getIndices($indexHandle, excludeSearchOnly: false);

		// TODO handle raw search - If $options['raw'] is truthy.

		/** @var SearchResult $result */
		$result = $this->meiliClient
			->index($index->indexId)
			->search($query, $searchParams, $options);

		return $result;
	}

	public function multisearch(array $indexHandles, string $query, array $searchParams = [], array $options = []): array
	{
		/** @var Index $index */
		$indexes = Plugin::getInstance()->settings->getIndices($indexHandles, excludeSearchOnly: false);
		$indexIds = array_map(fn($index) => $index->indexId, $indexes);


		// TODO handle raw search - If $options['raw'] is truthy.

		// /** @var SearchResult $result */
		// $result = $this->meiliClient
		// 	->index($indexIds)
		// 	->search($query, $searchParams, $options);

		$client = $this->meiliClient;
		$result = $client->multiSearch([
			(new SearchQuery())
			->setIndexUid('dev_experiences')
			->setQuery($query),
			(new SearchQuery())
			->setIndexUid('dev_people')
			->setQuery($query),
			(new SearchQuery())
			->setIndexUid('dev_pages')
			->setQuery($query),
			(new SearchQuery())
			->setIndexUid('dev_updates')
			->setQuery($query),
		]);

		return $result;
	}
}
