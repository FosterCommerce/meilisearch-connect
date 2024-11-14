<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\models\Index;
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
	 */
	public function search(string $indexHandle, string $query, array $searchParams = [], array $options = []): SearchResult
	{
		/** @var Index $index */
		$index = Plugin::getInstance()->settings->getIndices($indexHandle);

		// TODO handle raw search - If $options['raw'] is truthy.

		/** @var SearchResult $result */
		$result = $this->meiliClient
			->index($index->indexId)
			->search($query, $searchParams, $options);

		return $result;
	}
}
