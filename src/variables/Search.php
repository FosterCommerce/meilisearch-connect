<?php

namespace fostercommerce\meilisearch\variables;

use Craft;
use craft\web\Request;
use craft\web\twig\variables\Paginate;
use fostercommerce\meilisearch\Plugin;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Search extends Component
{
	/**
	 * @param array<non-empty-string, mixed> $searchParams
	 * @param array<non-empty-string, mixed> $options
	 * @return array{results: array<int, array<mixed, mixed>>, pagination: Paginate}
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
		$results = Plugin::getInstance()->search->search($indexHandle, $query, $searchParams, $options);



		return [
			'results' => $results['results'],
			'pagination' => Craft::createObject([
				'class' => Paginate::class,
				'first' => $results['pagination']['first'],
				'last' => $results['pagination']['last'],
				'total' => $results['pagination']['total'],
				'currentPage' => $results['pagination']['currentPage'],
				'totalPages' => $results['pagination']['totalPages'],
			]),
		];
	}
}
