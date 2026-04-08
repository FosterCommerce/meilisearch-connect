<?php

namespace fostercommerce\meilisearch\variables;

use Craft;
use craft\web\Request;
use craft\web\twig\variables\Paginate;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Exceptions\ApiException;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * @phpstan-type MeilisearchSearchResult array{
 *     results: array<int, array<array-key, mixed>>,
 *     facetDistribution: array<string, mixed>,
 *     processingTimeMs: int,
 *     pagination: Paginate,
 *     error?: ApiException,
 * }
 */
class Search extends Component
{
	/**
	 * @param array<non-empty-string, mixed> $searchParams
	 * @param array<non-empty-string, mixed> $options
	 * @return MeilisearchSearchResult
	 * @throws InvalidConfigException
	 */
	public function search(string $indexHandle, string $query, array $searchParams = [], array $options = []): array
	{
		/** @var Request $request */
		$request = Craft::$app->request;
		$pageNum = $request->getPageNum();
		$searchParams = [
			'page' => $pageNum,
			...$searchParams,
		];

		try {
			$results = Plugin::getInstance()->search->search($indexHandle, $query, $searchParams, $options);
		} catch (ApiException $apiException) {
			Craft::error($apiException->getMessage(), 'meilisearch-connect');
			return [
				'error' => $apiException,
				'results' => [],
				'facetDistribution' => [],
				'processingTimeMs' => 0,
				'pagination' => new Paginate([
					'first' => 0,
					'last' => 0,
					'currentPage' => 1,
				]),
			];
		}

		$offset = ($results->getHitsPerPage() * ($results->getPage() - 1));

		$hits = $results->getHits();
		$hitsCount = count($hits);
		return [
			'results' => $hits,
			'facetDistribution' => $results->getFacetDistribution(),
			'processingTimeMs' => $results->getProcessingTimeMs(),
			'pagination' => new Paginate([
				'first' => $offset + 1,
				'last' => $offset + $hitsCount,
				'total' => $results->getTotalHits(),
				'currentPage' => $results->getPage(),
				'totalPages' => $results->getTotalPages(),
			]),
		];
	}

	public function multisearch(array $indexHandles, string $query, array $searchParams = [], array $options = []): array
	{
		/** @var Request $request */
		$request = Craft::$app->request;
		$pageNum = $request->getPageNum();
		$searchParams = [
			'page' => $pageNum,
			...$searchParams,
		];

		try {
			$results = Plugin::getInstance()->search->multisearch($indexHandles, $query, $searchParams, $options);
		} catch (ApiException $apiException) {
			Craft::error($apiException->getMessage(), 'meilisearch-connect');
			return [
				'error' => $apiException,
				'results' => [],
				'facetDistribution' => [],
				'processingTimeMs' => 0,
				'pagination' => new Paginate([
					'first' => 0,
					'last' => 0,
					'currentPage' => 1,
				]),
			];
		}

		$offset = ($results->getLimit() * ($results->getPage() - 1));

		$hits = $results->getHits();
		$hitsCount = count($hits);

		return [
			'results' => $hits,
			'facetDistribution' => $results->getFacetDistribution(),
			'processingTimeMs' => $results->getProcessingTimeMs(),
			'pagination' => new Paginate([
				'first' => $offset + 1,
				'last' => $offset + $hitsCount,
				'total' => $results->getEstimatedTotalHits(),
				'currentPage' => $results->getPage() ?? 1,
				'totalPages' => $results->getEstimatedTotalHits() / $results->getLimit() ?? 1,
			]),
		];
	}
}
