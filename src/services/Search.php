<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\Plugin;
use Meilisearch\Search\SearchResult;
use yii\base\Component;

class Search extends Component
{
	use Meili;

	public function init(): void
	{
		$this->initMeiliClient();
	}

	/**
	 * @param array<non-empty-string, mixed> $searchParams
	 * @param array<non-empty-string, mixed> $options
	 * @return array{
	 *     results: array<int, array<mixed, mixed>>,
	 *     pagination: array<non-empty-string, int|null>
	 * }
	 */
	public function search(string $indexName, string $query, array $searchParams = [], array $options = []): array
	{
		$index = Plugin::getInstance()->settings->getIndices($indexName)[0] ?? null;
		if ($index === null) {
			throw new \RuntimeException('Index not found');
		}

		// TODO handle raw search - If $options['raw'] is truthy.

		/** @var SearchResult $result */
		$result = $this->meiliClient
			->index($index->indexId)
			->search($query, $searchParams, $options);

		$offset = ($result->getHitsPerPage() * ($result->getPage() - 1));

		return [
			'results' => $result->getHits(),
			'pagination' => [
				'first' => $offset + 1,
				'last' => $offset + count($result->getHits()),
				'total' => $result->getTotalHits(),
				'currentPage' => $result->getPage(),
				'totalPages' => $result->getTotalPages(),
			],
		];
	}
}
