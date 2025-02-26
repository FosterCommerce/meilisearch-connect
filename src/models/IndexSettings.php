<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

/**
 * @phpstan-type FacetingParams array{
 *     maxValuesPerFacet?: int,
 *     sortFacetValuesBy?: array<non-empty-string, 'count'|'alpha'>,
 *  }
 *
 * @phpstan-type PaginationParams array{
 *     maxTotalHits: positive-int,
 * }
 *
 * @phpstan-type TypoToleranceParams array{
 *     enabled: bool,
 *     minWordSizeForTypos: array{
 *         oneTypo: int,
 *         twoTypos: int
 *     },
 *     disableOnWords: non-empty-string[],
 *     disableOnAttributes: non-empty-string[]
 * }
 *
 * @phpstan-type EmbeddedParams array<non-empty-string, array{
 *          source: string,
 *          url: string,
 *          apiKey: string,
 *          model: string,
 *          documentTemplate: string,
 *          documentTemplateMaxBytes: int,
 *          dimensions: int,
 *          revision: string,
 *          distribution: array{
 *              mean: float,
 *              sigma: float
 *          },
 *          request: array<array-key, mixed>,
 *          response: array<array-key, mixed>,
 *          headers: array<array-key, mixed>,
 *          binaryQuantized: bool
 *      }
 *  >
 */
class IndexSettings extends Model
{
	public const DEFAULT_PRIMARY_KEY = 'id';

	/**
	 * Primary key of the index. If not specified, Meilisearch guesses your primary key from the first document you add to the index.
	 *
	 * This plugin defaults the primary key to `id` which matches most Craft elements.
	 *
	 * Set this to null to let Meilisearch infer the primary key.
	 */
	public ?string $primaryKey = self::DEFAULT_PRIMARY_KEY;

	/**
	 * List of strings Meilisearch should parse as a single term.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $dictionary = null;

	/**
	 * Fields displayed in the returned documents.
	 *
	 * Default: ["*"]
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $displayedAttributes = null;

	/**
	 * Search returns documents with distinct (different) values of the given field.
	 *
	 * Default: null
	 *
	 * @var ?non-empty-string
	 */
	public ?string $distinctAttribute = null;

	/**
	 * With Meilisearch, you can create faceted search interfaces. This setting allows you to:
	 * - Define the maximum number of values returned by the facets search parameter
	 * - Sort facet values by value count or alphanumeric order
	 *
	 * @var ?FacetingParams
	 */
	public ?array $faceting = null;

	/**
	 * Attributes in the `filterableAttributes` list can be used as filters or facets.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $filterableAttributes = null;

	/**
	 * Pagination settings (e.g., maxTotalHits).
	 *
	 * @var ?PaginationParams
	 */
	public ?array $pagination = null;

	/**
	 * Precision level when calculating the proximity ranking rule.
	 *
	 * Default: "byWord"
	 *
	 * @var null|'byWord'|'byAttribute'
	 */
	public ?string $proximityPrecision = null;

	/**
	 * Enable or disable facet search functionality.
	 *
	 * Default: true
	 *
	 * @since Meilisearch v1.12.0
	 */
	public ?bool $facetSearch = null;

	/**
	 * When Meilisearch should return results only matching the beginning of the query.
	 *
	 * Default: "indexingTime"
	 *
	 * @var null|'indexingTime'|'disabled'
	 *
	 * @since Meilisearch v1.12.0
	 */
	public ?string $prefixSearch = null;

	/**
	 * Ranking rules are built-in rules that rank search results according to certain criteria. They are applied in the same order in which they appear in the `rankingRules` array.
	 *
	 * Default: ["words", "typo", "proximity", "attribute", "sort", "exactness"]
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $ranking = null;

	/**
	 * The values associated with attributes in the `searchableAttributes` list are searched for matching query words. The order of the list also determines the attribute ranking order.
	 *
	 * By default, the `searchableAttributes` array is equal to all fields in your dataset. This behavior is represented by the value `["*"]`.
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $searchableAttributes = null;

	/**
	 * Maximum duration of a search query (in milliseconds).
	 *
	 * Default: null, or 1500
	 */
	public ?int $searchCutoffMs = null;

	/**
	 * List of characters delimiting where one term begins and ends.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $separatorTokens = null;

	/**
	 * List of characters that do NOT delimit where one term begins and ends.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $nonSeparatorTokens = null;

	/**
	 * Attributes that can be used when sorting search results using the sort search parameter.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $sortableAttributes = null;

	/**
	 * List of words ignored by Meilisearch when present in search queries.
	 *
	 * Default: []
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $stopWords = null;

	/**
	 * List of associated words treated similarly.
	 * Example: ["wolverine" => ["xmen", "logan"], "lol" => ["laugh out loud"]]
	 *
	 * Default: {}
	 *
	 * @var ?array<non-empty-string, non-empty-string[]>
	 */
	public ?array $synonyms = null;

	/**
	 * Typo tolerance settings.
	 *
	 * Default: (see Meilisearch docs for default object)
	 *
	 * @var ?TypoToleranceParams
	 */
	public ?array $typoTolerance = null;

	/**
	 * Embedder configurations for meaning-based (semantic) search queries.
	 *
	 * Default: (see Meilisearch docs for default object)
	 *
	 * @var ?EmbeddedParams
	 */
	public ?array $embedders = null;
}
