<?php

namespace fostercommerce\meilisearch\variables;

use Craft;
use craft\web\twig\variables\Paginate;
use fostercommerce\meilisearch\Plugin;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Search extends Component
{
	/**
	 * @throws InvalidConfigException
	 */
	public function search(string $indexName, string $query, array $searchParams = [], array $options = []): array
	{
		$pageNum = Craft::$app->request->getPageNum();
		$searchParams = [
			'page' => $pageNum,
			...$searchParams,
		];
		$results = Plugin::getInstance()->search->search($indexName, $query, $searchParams, $options);

		return [
			'results' => $results['results'],
			'pagination' => Craft::createObject([
				'class' => Paginate::class,
				...$results['pagination'],
			]),
		];
	}
}
