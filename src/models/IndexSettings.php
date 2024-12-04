<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

/**
 * @phpstan-type FacetingParams array{
 *     maxValuesPerFacet?: int,
 *     sortFacetValuesBy?: array<non-empty-string, 'count'|'alpha'>
 *  }
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
	 * Ranking rules are built-in rules that rank search results according to certain criteria. They are applied in the same order in which they appear in the `rankingRules` array.
	 *
	 * @var non-empty-string[]
	 */
	public array $ranking = [];

	/**
	 * The values associated with attributes in the `searchableAttributes` list are searched for matching query words. The order of the list also determines the attribute ranking order.
	 *
	 * By default, the `searchableAttributes` array is equal to all fields in your dataset. This behavior is represented by the value `["*"]`.
	 *
	 * @var ?non-empty-string[]
	 */
	public ?array $searchableAttributes = null;

	/**
	 * Attributes in the `filterableAttributes` list can be used as filters or facets.
	 *
	 * @var non-empty-string[]
	 */
	public array $filterableAttributes = [];

	/**
	 * Attributes that can be used when sorting search results using the sort search parameter.
	 *
	 * @var non-empty-string[]
	 */
	public array $sortableAttributes = [];

	/**
	 * With Meilisearch, you can create faceted search interfaces. This setting allows you to:
	 * - Define the maximum number of values returned by the facets search parameter
	 * - Sort facet values by value count or alphanumeric order
	 *
	 * @var ?FacetingParams
	 */
	public ?array $faceting = null;
}
