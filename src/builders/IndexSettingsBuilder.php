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

	public function withPrimaryKey(?string $primaryKey): self
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}

	/**
	 * @param non-empty-string[] $ranking
	 */
	public function withRanking(array $ranking): self
	{
		$this->ranking = $ranking;
		return $this;
	}

	/**
	 * @param ?non-empty-string[] $searchableAttributes
	 */
	public function withSearchableAttributes(?array $searchableAttributes): self
	{
		$this->searchableAttributes = $searchableAttributes;
		return $this;
	}

	/**
	 * @param non-empty-string[] $filterableAttributes
	 */
	public function withFilterableAttributes(array $filterableAttributes): self
	{
		$this->filterableAttributes = $filterableAttributes;
		return $this;
	}

	/**
	 * @param non-empty-string[] $sortableAttributes
	 */
	public function withSortableAttributes(array $sortableAttributes): self
	{
		$this->sortableAttributes = $sortableAttributes;
		return $this;
	}

	/**
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
	 * @return array<string, mixed>
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
