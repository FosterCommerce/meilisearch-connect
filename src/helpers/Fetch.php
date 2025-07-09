<?php

namespace fostercommerce\meilisearch\helpers;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use fostercommerce\meilisearch\models\Index;
use Generator;

/**
 * @template TQuery of ElementQueryInterface|callable(): ElementQueryInterface
 * @template TElement of ElementInterface
 * @phpstan-type TransformerFn callable(TElement): array<non-empty-string, mixed>
 * @phpstan-type PagesFn callable(Index): int
 * @phpstan-type FetchFn callable(Index, null|string|int): Generator<int, array<non-empty-string, mixed>>
 */
class Fetch
{
	/**
	 * @param TQuery $query
	 * @param TransformerFn $transformer
	 * @return array{
	 *     query: ElementQueryInterface|callable(): ElementQueryInterface,
	 *     fetch: FetchFn,
	 * }
	 */
	public static function propertiesFromElementQuery(ElementQueryInterface|callable $query, callable $transformer): array
	{
		return [
			'query' => $query,
			'fetch' => self::createFetchFn($transformer),
		];
	}

	/**
	 * @param TransformerFn $transformer
	 * @return FetchFn
	 */
	public static function createFetchFn(callable $transformer): callable
	{
		return static function (Index $index, null|string|int $identifier) use ($transformer) {
			/** @var TQuery $indexQuery */
			$indexQuery = $index->query;

			if (is_callable($indexQuery)) {
				$indexQuery = $indexQuery();
			}

			if ($identifier !== null) {
				$indexQuery->id($identifier);
			}

			$page = 0;
			$pageSize = $index->pageSize ?? Index::DEFAULT_PAGE_SIZE;

			do {
				$query = $indexQuery->offset($page * $pageSize)->limit($pageSize);
				$items = $query->all();

				yield array_map($transformer, $items);

				++$page;
			} while (count($items) === $pageSize);
		};
	}
}
