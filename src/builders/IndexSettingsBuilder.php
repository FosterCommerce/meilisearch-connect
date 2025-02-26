<?php

namespace fostercommerce\meilisearch\builders;

use fostercommerce\meilisearch\models\IndexSettings;

/**
 * @phpstan-import-type FacetingParams from IndexSettings
 * @phpstan-import-type PaginationParams from IndexSettings
 * @phpstan-import-type TypoToleranceParams from IndexSettings
 * @phpstan-import-type EmbeddedParams from IndexSettings
 */
class IndexSettingsBuilder
{
	private ?string $primaryKey = IndexSettings::DEFAULT_PRIMARY_KEY;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $dictionary = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $displayedAttributes = null;

	/**
	 * @var ?non-empty-string
	 */
	private ?string $distinctAttribute = null;

	/**
	 * @var ?FacetingParams
	 */
	private ?array $faceting = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $filterableAttributes = null;

	/**
	 * @var ?PaginationParams
	 */
	private ?array $pagination = null;

	/**
	 * @var null|'byWord'|'byAttribute'
	 */
	private ?string $proximityPrecision = null;

	/**
	 * @since Meilisearch v1.12.0
	 */
	private ?bool $facetSearch = null;

	/**
	 * @var null|'indexingTime'|'disabled'
	 *
	 * @since Meilisearch v1.12.0
	 */
	private ?string $prefixSearch = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $ranking = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $searchableAttributes = null;

	private ?int $searchCutoffMs = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $separatorTokens = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $nonSeparatorTokens = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $sortableAttributes = null;

	/**
	 * @var ?non-empty-string[]
	 */
	private ?array $stopWords = null;

	/**
	 * @var ?array<non-empty-string, non-empty-string[]>
	 */
	private ?array $synonyms = null;

	/**
	 * @var ?TypoToleranceParams
	 */
	private ?array $typoTolerance = null;

	/**
	 * @var ?EmbeddedParams
	 */
	private ?array $embedders = null;

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
	 * @param array<non-empty-string, non-empty-string[]> $synonyms
	 */
	public function withSynonyms(array $synonyms): self
	{
		$this->synonyms = $synonyms;
		return $this;
	}

	/**
	 * List of strings Meilisearch should parse as a single term.
	 * Default: []
	 *
	 * @param ?non-empty-string[] $dictionary
	 */
	public function withDictionary(?array $dictionary): self
	{
		$this->dictionary = $dictionary;
		return $this;
	}

	/**
	 * Fields displayed in the returned documents.
	 * Default: ["*"]
	 *
	 * @param ?non-empty-string[] $displayedAttributes
	 */
	public function withDisplayedAttributes(?array $displayedAttributes): self
	{
		$this->displayedAttributes = $displayedAttributes;
		return $this;
	}

	/**
	 * Search returns documents with distinct (different) values of the given field.
	 * Default: null
	 *
	 * @param ?non-empty-string $distinctAttribute
	 */
	public function withDistinctAttribute(?string $distinctAttribute): self
	{
		$this->distinctAttribute = $distinctAttribute;
		return $this;
	}

	/**
	 * Pagination settings (e.g., maxTotalHits).
	 *
	 * @param ?PaginationParams $pagination
	 */
	public function withPagination(?array $pagination): self
	{
		$this->pagination = $pagination;
		return $this;
	}

	/**
	 * Precision level when calculating the proximity ranking rule.
	 * Default: "byWord"
	 *
	 * @param null|'byWord'|'byAttribute' $proximityPrecision
	 */
	public function withProximityPrecision(?string $proximityPrecision): self
	{
		$this->proximityPrecision = $proximityPrecision;
		return $this;
	}

	/**
	 * Enable or disable facet search functionality.
	 * Default: true
	 *
	 * @since Meilisearch v1.12.0
	 */
	public function withFacetSearch(?bool $facetSearch): self
	{
		$this->facetSearch = $facetSearch;
		return $this;
	}

	/**
	 * When Meilisearch should return results only matching the beginning of the query.
	 * Default: "indexingTime"
	 *
	 * @since Meilisearch v1.12.0
	 *
	 * @param null|'indexingTime'|'disabled' $prefixSearch
	 */
	public function withPrefixSearch(?string $prefixSearch): self
	{
		$this->prefixSearch = $prefixSearch;
		return $this;
	}

	/**
	 * Maximum duration of a search query (in milliseconds).
	 * Default: null, or 1500
	 */
	public function withSearchCutoffMs(?int $searchCutoffMs): self
	{
		$this->searchCutoffMs = $searchCutoffMs;
		return $this;
	}

	/**
	 * List of characters delimiting where one term begins and ends.
	 * Default: []
	 *
	 * @param ?non-empty-string[] $separatorTokens
	 */
	public function withSeparatorTokens(?array $separatorTokens): self
	{
		$this->separatorTokens = $separatorTokens;
		return $this;
	}

	/**
	 * List of characters that do NOT delimit where one term begins and ends.
	 * Default: []
	 *
	 * @param ?non-empty-string[] $nonSeparatorTokens
	 */
	public function withNonSeparatorTokens(?array $nonSeparatorTokens): self
	{
		$this->nonSeparatorTokens = $nonSeparatorTokens;
		return $this;
	}

	/**
	 * List of words ignored by Meilisearch when present in search queries.
	 * Default: []
	 *
	 * @param ?non-empty-string[] $stopWords
	 */
	public function withStopWords(?array $stopWords): self
	{
		$this->stopWords = $stopWords;
		return $this;
	}

	/**
	 * Typo tolerance settings.
	 * Default: (see Meilisearch docs for default object)
	 *
	 * @param ?TypoToleranceParams $typoTolerance
	 */
	public function withTypoTolerance(?array $typoTolerance): self
	{
		$this->typoTolerance = $typoTolerance;
		return $this;
	}

	/**
	 * Embedder configurations for meaning\-based (semantic) search queries.
	 * Default: (see Meilisearch docs for default object)
	 *
	 * @param ?EmbeddedParams $embedders
	 */
	public function withEmbedders(?array $embedders): self
	{
		$this->embedders = $embedders;
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
			'dictionary' => $this->dictionary,
			'displayedAttributes' => $this->displayedAttributes,
			'distinctAttribute' => $this->distinctAttribute,
			'faceting' => $this->faceting,
			'filterableAttributes' => $this->filterableAttributes,
			'pagination' => $this->pagination,
			'proximityPrecision' => $this->proximityPrecision,
			'facetSearch' => $this->facetSearch,
			'prefixSearch' => $this->prefixSearch,
			'ranking' => $this->ranking,
			'searchableAttributes' => $this->searchableAttributes,
			'searchCutoffMs' => $this->searchCutoffMs,
			'separatorTokens' => $this->separatorTokens,
			'nonSeparatorTokens' => $this->nonSeparatorTokens,
			'sortableAttributes' => $this->sortableAttributes,
			'stopWords' => $this->stopWords,
			'synonyms' => $this->synonyms,
			'typoTolerance' => $this->typoTolerance,
			'embedders' => $this->embedders,
		];
	}
}
