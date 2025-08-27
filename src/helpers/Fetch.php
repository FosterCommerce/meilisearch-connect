<?php

namespace fostercommerce\meilisearch\helpers;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use fostercommerce\meilisearch\models\ElementQueryFetchExtra;
use fostercommerce\meilisearch\models\Index;
use Generator;

/**
 * @template TQuery of ElementQueryInterface|callable(): ElementQueryInterface
 * @template TElement of ElementInterface
 * @phpstan-import-type Document from DocumentList
 * @phpstan-type RegisterDependencyFn callable(ElementInterface): void
 * @phpstan-type TransformerFn callable(TElement, RegisterDependencyFn): (Document|Document[]|null)
 * @phpstan-type PagesFn callable(Index): int
 * @phpstan-type FetchFn callable(Index, null|string|int, mixed): (Generator<DocumentList[]>|DocumentList[]|DocumentList)
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
		return static function (Index $index, null|string|int $sourceHandle, ?ElementQueryFetchExtra $extra = null) use ($transformer) {
			/** @var TQuery $indexQuery */
			$indexQuery = $index->query;

			if (is_callable($indexQuery)) {
				$indexQuery = $indexQuery();
			}

			if ($sourceHandle !== null) {
				if ($extra?->anyStatus ?? false) {
					$indexQuery->status(null);
				}

				$indexQuery->id($sourceHandle);
			}

			$page = 0;
			$pageSize = $index->pageSize ?? Index::DEFAULT_PAGE_SIZE;

			do {
				$query = $indexQuery->offset($page * $pageSize)->limit($pageSize);
				$items = $query->all();

				yield array_map(static function ($item) use (&$transformer): DocumentList {
					/** @var string[] $dependencies */
					$dependencies = [];

					$registerDependency = function (ElementInterface $element) use (&$dependencies): void {
						if ($element->id === null) {
							throw new \RuntimeException('Unable to register an element without an ID as dependency');
						}

						$dependencies[] = (string) $element->id;
					};

					$documentOrDocuments = $transformer($item, $registerDependency);

					// Transformers are allowed to return a single document.
					// Make sure the fetch function always returns an array of documents.
					return new DocumentList($documentOrDocuments ?? [], $item->id, $dependencies);
				}, $items);

				++$page;
			} while (count($items) === $pageSize);
		};
	}
}
