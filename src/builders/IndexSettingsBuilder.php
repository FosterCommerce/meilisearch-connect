<?php

namespace fostercommerce\meilisearch\builders;

use fostercommerce\meilisearch\models\IndexSettings;

/**
 * @phpstan-import-type FacetingParams from IndexSettings
 */
class IndexSettingsBuilder
{
	private ?string $primaryKey = IndexSettings::DEFAULT_PRIMARY_KEY;

	/**
	 * @var non-empty-string[]
	 */
	private array $ranking = [];

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $searchableAttributes = null;

	/**
	 * @var non-empty-string[]
	 */
	private array $filterableAttributes = [];

	/**
	 * @var non-empty-string[]
	 */
	private array $sortableAttributes = [];

	/**
	 * @var FacetingParams
	 */
	private array $faceting = [];

	public static function create(): self
	{
		return new self();
	}

	/**
	 * Primary key of the index. If not specified, Meilisearch guesses your primary key from the first document you add to the index.
	 *
	 * This plugin defaults the primary key to `id` which matches most Craft elements.
	 *
	 * Set this to null to let Meilisearch infer the primary key.
	 */
	public function withPrimaryKey(?string $primaryKey): self
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}

	/**
	 * Ranking rules are built-in rules that rank search results according to certain criteria. They are applied in the same order in which they appear in the `rankingRules` array.
	 *
	 * @param non-empty-string[] $ranking
	 */
	public function withRanking(array $ranking): self
	{
		$this->ranking = $ranking;
		return $this;
	}

	/**
	 * The values associated with attributes in the `searchableAttributes` list are searched for matching query words. The order of the list also determines the attribute ranking order.
	 *
	 * By default, the `searchableAttributes` array is equal to all fields in your dataset. This behavior is represented by the value `["*"]`.
	 *
	 * @param ?non-empty-string[] $searchableAttributes
	 */
	public function withSearchableAttributes(?array $searchableAttributes): self
	{
		$this->searchableAttributes = $searchableAttributes;
		return $this;
	}

	/**
	 * Attributes in the `filterableAttributes` list can be used as filters or facets.
	 *
	 * @param non-empty-string[] $filterableAttributes
	 */
	public function withFilterableAttributes(array $filterableAttributes): self
	{
		$this->filterableAttributes = $filterableAttributes;
		return $this;
	}

	/**
	 * Attributes that can be used when sorting search results using the sort search parameter.
	 *
	 * @param non-empty-string[] $sortableAttributes
	 */
	public function withSortableAttributes(array $sortableAttributes): self
	{
		$this->sortableAttributes = $sortableAttributes;
		return $this;
	}

	/**
	 * With Meilisearch, you can create faceted search interfaces. This setting allows you to:
	 * - Define the maximum number of values returned by the facets search parameter
	 * - Sort facet values by value count or alphanumeric order
	 *
	 * @param FacetingParams $faceting
	 */
	public function withFaceting(array $faceting): self
	{
		$this->faceting = $faceting;
		return $this;
	}

	/**
	 * Build and return the configuration array
	 *
	 * @return array<non-empty-string, mixed>
	 */
	public function build(): array
	{
		return [
			'primaryKey' => $this->primaryKey,
			'ranking' => $this->ranking,
			'searchableAttributes' => $this->searchableAttributes,
			'filterableAttributes' => $this->filterableAttributes,
			'sortableAttributes' => $this->sortableAttributes,
			'faceting' => $this->faceting,
		];
	}
}
