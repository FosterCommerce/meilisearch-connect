<?php

namespace fostercommerce\meilisearch\helpers;

use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use Generator;

/**
 * @template TKey of array-key
 * @template TElement of ElementInterface
 * @phpstan-type TransformerFn callable(TElement): array<non-empty-string, mixed>
 * @phpstan-type PagesFn callable(null|int): int
 * @phpstan-type FetchFn callable(null|string|int, null|int): Generator<int, array<non-empty-string, mixed>>
 */
class Fetch
{
	/**
	 * @var int
	 */
	public const DEFAULT_PAGE_SIZE = 100;

	/**
	 * @param ElementQuery<TKey, TElement> $query
	 * @param TransformerFn $transformer
	 * @return array{
	 *     pages: PagesFn,
	 *     fetch: FetchFn,
	 * }
	 */
	public static function createIndexFns(ElementQuery $query, callable $transformer): array
	{
		return [
			'pages' => self::createPageCountFn($query),
			'fetch' => self::createFetchFn($query, $transformer),
		];
	}

	/**
	 * @param ElementQuery<TKey, TElement> $query
	 * @return PagesFn
	 */
	public static function createPageCountFn(ElementQuery $query): callable
	{
		return static function (?int $pageSize) use ($query): int {
			/** @var int $count */
			$count = $query->count();
			return (int) ceil($count / ($pageSize ?? self::DEFAULT_PAGE_SIZE));
		};
	}

	/**
	 * @param ElementQuery<TKey, TElement> $query
	 * @param TransformerFn $transformer
	 * @return FetchFn
	 */
	public static function createFetchFn(ElementQuery $query, callable $transformer): callable
	{
		return static function (null|string|int $identifier, ?int $pageSize) use ($query, $transformer) {
			if ($identifier !== null) {
				$query->id($identifier);
			}

			$page = 0;
			$pageSize ??= self::DEFAULT_PAGE_SIZE;

			do {
				$query = $query->offset($page * $pageSize)->limit($pageSize);
				$items = $query->all();

				yield array_map($transformer, $items);

				++$page;
			} while (count($items) === $pageSize);
		};
	}
}
