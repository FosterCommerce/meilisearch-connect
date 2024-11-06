<?php

namespace fostercommerce\meilisearch\builders;

use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use fostercommerce\meilisearch\helpers\Fetch;

class IndexBuilder
{
	private ?string $indexId = null;

	private ?int $pageSize = null;

	/**
	 * @var array<non-empty-string, mixed>
	 */
	private array $settings;

	/**
	 * @var ?callable
	 */
	private mixed $pagesFn = null;

	/**
	 * @var ?callable
	 */
	private mixed $fetchFn = null;

	/**
	 * @param array<non-empty-string, mixed> $settings
	 */
	public static function fromSettings(array $settings): self
	{
		$self = new self();
		$self->settings = $settings;
		return $self;
	}

	public function withIndexId(string $indexId): self
	{
		$this->indexId = $indexId;
		return $this;
	}

	public function withPageSize(int $pageSize): self
	{
		$this->pageSize = $pageSize;
		return $this;
	}

	public function withElementQuery(ElementQueryInterface $query, callable $transformer): self
	{
		if (! $query instanceof ElementQuery) {
			throw new \RuntimeException('Query must be instance of ' . ElementQuery::class);
		}

		['pages' => $pages, 'fetch' => $fetch] = Fetch::createIndexFns($query, $transformer);

		$this->pagesFn = $pages;
		$this->fetchFn = $fetch;

		return $this;
	}

	public function withPagesFn(callable $fn): self
	{
		$this->pagesFn = $fn;
		return $this;
	}

	public function withFetchFn(callable $fn): self
	{
		$this->fetchFn = $fn;
		return $this;
	}

	/**
	 * @return array<non-empty-string, mixed>
	 */
	public function build(): array
	{
		if ($this->fetchFn === null) {
			throw new \RuntimeException('Fetch function is required');
		}

		return [
			'indexId' => $this->indexId,
			'settings' => $this->settings,
			'pageSize' => $this->pageSize,
			'pages' => $this->pagesFn,
			'fetch' => $this->fetchFn,
		];
	}
}
