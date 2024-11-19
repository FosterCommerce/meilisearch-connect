<?php

namespace fostercommerce\meilisearch\builders;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use fostercommerce\meilisearch\helpers\Fetch;

class IndexBuilder
{
	private ?string $indexId = null;

	private ?int $pageSize = null;

	/**
	 * @var ?array<non-empty-string, mixed>
	 */
	private ?array $settings = null;

	private mixed $query = null;

	private bool $autoSync = true;

	/**
	 * @var string[]
	 */
	private array $activeStatuses = [Element::STATUS_ENABLED, Entry::STATUS_LIVE];

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

	/**
	 * Create a search only index
	 */
	public static function create(): self
	{
		return new self();
	}

	/**
	 * The actual ID of the index on Meilisearch.
	 *
	 * This would usually be set via an environment variable for multiple environments.
	 */
	public function withIndexId(string $indexId): self
	{
		$this->indexId = $indexId;
		return $this;
	}

	/**
	 * Set the page size for paginated data.
	 */
	public function withPageSize(int $pageSize): self
	{
		$this->pageSize = $pageSize;
		return $this;
	}

	/**
	 * Configures the pages and fetch callables based on an element query and a transformer callable.
	 */
	public function withElementQuery(ElementQueryInterface $query, callable $transformer): self
	{
		if (! $query instanceof ElementQuery) {
			throw new \RuntimeException('Query must be instance of ' . ElementQuery::class);
		}

		['query' => $query, 'fetch' => $fetch] = Fetch::propertiesFromElementQuery($query, $transformer);

		$this->query = $query;
		$this->fetchFn = $fetch;

		return $this;
	}

	public function withQuery(mixed $query): self
	{
		$this->query = $query;
		return $this;
	}

	public function withAutoSync(bool $enabled = true): self
	{
		$this->autoSync = $enabled;
		return $this;
	}

	/**
	 * @param string[] $statuses
	 */
	public function withActiveStatuses(array $statuses = [Element::STATUS_ENABLED, Entry::STATUS_LIVE]): self
	{
		$this->activeStatuses = $statuses;
		return $this;
	}

	/**
	 * Set the callable that is used by the plugin when reporting progress on synchronization tasks.
	 *
	 * Example:
	 *
	 * ```php
	 * static function (Index $index, ?int $pageSize): int {
	 *   return ceil(Entry::find()->count() / ($pageSize ?? 100));
	 * }
	 * ```
	 */
	public function withPagesFn(?callable $fn): self
	{
		$this->pagesFn = $fn;
		return $this;
	}

	/**
	 * Set the callback used to fetch data that should be synchronized to the index in a Meilisearch instance.
	 *
	 * The callable will possibly receive an identifier and a page size argument.
	 *
	 * It must return either an array of associative arrays, or a Generator, which yields associative arrays in chunks.
	 *
	 * It is preferable to use a generator so that the entire resultset isn't loaded into memory and sent to Meilisearch in a single chunk.
	 *
	 * See the {@see \fostercommerce\meilisearch\helpers\Fetch::createFetchFn} implementation for an example of a callable that returns a {@see \Generator}
	 *
	 * Example of a callable that returns an array:
	 *
	 * ```php
	 * static function (Index $index, ?int $id): array {
	 *   return collect(Entry::find()->all())
	 *     ->map(static fn ($entry) => [
	 *       'id' => $entry->id,
	 *       'title' => $entry->title,
	 *       'description' => $entry->description,
	 *     ]);
	 * }
	 * ```
	 */
	public function withFetchFn(?callable $fn): self
	{
		$this->fetchFn = $fn;
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
			'indexId' => $this->indexId,
			'settings' => $this->settings,
			'pageSize' => $this->pageSize,
			'query' => $this->query,
			'autoSync' => $this->autoSync,
			'activeStatuses' => $this->activeStatuses,
			'pages' => $this->pagesFn,
			'fetch' => $this->fetchFn,
		];
	}
}
