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

	/**
	 * @param array<non-empty-string, mixed> $indexHandles
	 * @param array<non-empty-string, mixed> $searchParams
	 * @param array<non-empty-string, mixed> $options
	 * @throws ApiException if the underlying Meilisearch request fails
	 */
	public function multisearch(array $indexHandles, string $query, array $searchParams = [], array $options = [], bool $federatedSearch = true): SearchResult
	{
		/** @var array<Index> $indexes */
		$indexes = Plugin::getInstance()->settings->getIndices($indexHandles, excludeSearchOnly: false);
		$indexIds = array_map(fn ($index): string => $index->getIndexId(), $indexes);

		$searchable = array_map(fn ($indexId): SearchQuery =>
			(new SearchQuery())
				->setIndexUid($indexId)
				->setQuery($query), $indexIds);

		if ($federatedSearch) {
			/** @var int<0, max> $offset */
			$offset = $searchParams['page'] - 1;
			/** @var int<0, max> $limit */
			$limit = $searchParams['hitsPerPage'];

			$federation = new MultiSearchFederation();
			$federation->setLimit($limit)->setOffset($offset);

			$result = $this->meiliClient->multiSearch($searchable, $federation);
			return new SearchResult([
				...$result,
				'query' => $query,
				'page' => $searchParams['page'],
			]);
		}

		// TODO return results for non-federated multisearch
		// NOT CURRENTLY WORKING
		$multiResult = $this->meiliClient->multiSearch($searchable);
		array_map(fn ($resultsFromIndex): SearchResult => (new SearchResult([
			...$resultsFromIndex,
			'query' => $query,
		])), $multiResult['results']);
		return (new SearchResult(...$multiResult['results']));
	}
}
