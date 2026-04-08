<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Contracts\MultiSearchFederation;
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

	public function multisearch(array $indexHandles, string $query, array $searchParams = [], array $options = [], bool $federatedSearch = true): SearchResult
	{
		$indexes = Plugin::getInstance()->settings->getIndices($indexHandles, excludeSearchOnly: false);
		$indexIds = array_map(fn($index) => $index->indexId, $indexes);

		$searchable = array_map(fn($indexId) =>
			(new SearchQuery())
				->setIndexUid($indexId)
				->setQuery($query), $indexIds);

		if($federatedSearch === true) {
			$federation = new MultiSearchFederation();
			$federation->setLimit($searchParams['hitsPerPage'])->setOffset($searchParams['page'] - 1);

			$result = $this->meiliClient->multiSearch($searchable, $federation);
			return new SearchResult([...$result, 'query' => $query, 'page' => $searchParams['page']]);
		} else {
			$multiResult = $this->meiliClient->multiSearch($searchable);
			$result = array_map(fn($resultsFromIndex) => (new SearchResult([...$resultsFromIndex, 'query' => $query])), $multiResult['results']);
			dd(new SearchResult(...$multiResult['results']));
		}
	}
}
