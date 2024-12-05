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

		$offset = ($results->getHitsPerPage() * ($results->getPage() - 1));

		$hits = $results->getHits();
		$hitsCount = count($hits);
		return [
			'results' => $hits,
			'pagination' => Craft::createObject([
				'class' => Paginate::class,
				'first' => $offset + 1,
				'last' => $offset + $hitsCount,
				'total' => $results->getTotalHits(),
				'currentPage' => $results->getPage(),
				'totalPages' => $results->getTotalPages(),
			]),
		];
	}
}
