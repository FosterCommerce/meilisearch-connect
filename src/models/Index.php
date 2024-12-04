<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Element;
use craft\base\Model;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use Generator;

/**
 * @phpstan-type FetchResult array<int, array<non-empty-string, mixed>>
 * @phpstan-type FetchCallableReturn Generator<int, FetchResult>|FetchResult
 */
class Index extends Model
{
	/**
	 * Handle to access the index with.
	 *
	 * This should be the same across environments.
	 */
	public string $handle;

	/**
	 * The actual ID of the index on Meilisearch.
	 *
	 * This would usually be set via an environment variable for multiple environments.
	 */
	public string $indexId;

	public ?IndexSettings $settings = null;

	/**
	 * Optionally set the page size for paginated data.
	 */
	public ?int $pageSize = null;

	/**
	 * Optional query.
	 *
	 * If it's an {@see ElementQuery}, it can automatically be used to fetch the page count.
	 */
	public mixed $query = null;

	/**
	 * Automatically synchronize changes to data.
	 *
	 * `true` by default, however, it only automatically synchronizes data if `$query` is set to an {@see ElementQuery}.
	 */
	public bool $autoSync = true;

	/**
	 * Statuses to use during auto-sync to check whether an element is active or not.
	 *
	 * @var string[]
	 */
	public array $activeStatuses = [Element::STATUS_ENABLED, Entry::STATUS_LIVE];

	/**
	 * An optional callable that is used by the plugin when reporting progress on synchronization tasks.
	 *
	 * Example:
	 *
	 * ```php
	 * static function (Index $index): int {
	 *   return ceil(Entry::find()->count() / ($index->pageSize ?? 100));
	 * }
	 * ```
	 *
	 * Not required to be set when using search-only functionality.
	 *
	 * @var ?callable(Index): int
	 */
	private mixed $pages = null;

	/**
	 * A callback used to fetch data that should be synchronized to the index in a Meilisearch instance.
	 *
	 * The callable will receive a reference to it's Index and possibly an identifier.
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
	 *
	 * Not required to be set when using search-only functionality.
	 *
	 * If this callable returns `false` or a falsey value, then the item will not be indexed.
	 *
	 * @var ?callable(Index, null|string|int): FetchCallableReturn
	 */
	private $fetch;

	/**
	 * @param array{
	 *     handle?: non-empty-string,
	 *     indexId?: non-empty-string,
	 *     settings?: null|array<non-empty-string, mixed>,
	 * } $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['settings']) && $config['settings'] !== []) {
			$config['settings'] = new IndexSettings($config['settings']);
		}

		parent::__construct($config);
	}

	/**
	 * @param ?callable(Index): int $pages
	 */
	public function setPages(?callable $pages): void
	{
		$this->pages = $pages;
	}

	/**
	 * @param ?callable(Index, null|string|int): FetchCallableReturn $fetch
	 */
	public function setFetch(?callable $fetch): void
	{
		$this->fetch = $fetch;
	}

	public function init(): void
	{
		parent::init();

		if (! $this->settings instanceof IndexSettings) {
			$this->settings = new IndexSettings();
		}
	}

	public function getSettings(): IndexSettings
	{
		if (! $this->settings instanceof IndexSettings) {
			throw new \RuntimeException('Index is not configured correctly');
		}

		return $this->settings;
	}

	public function getPageCount(): ?int
	{
		if ($this->query instanceof ElementQueryInterface) {
			/** @var int $count */
			$count = $this->query->count();
			return $count;
		}

		$pagesFn = $this->pages;

		if ($pagesFn === null) {
			return null;
		}

		return $pagesFn($this);
	}

	/**
	 * @return Generator<int, FetchResult>
	 */
	public function execFetchFn(null|string|int $identifier = null): Generator
	{
		$fetchFn = $this->fetch;

		if ($fetchFn === null) {
			throw new \RuntimeException('Fetch callable is not configured correctly');
		}

		$result = $fetchFn($this, $identifier);

		if ($result instanceof Generator) {
			foreach ($result as $chunk) {
				yield $chunk;
			}
		} elseif (is_array($result)) {
			yield $result;
		} else {
			throw new \RuntimeException('Invalid return value from fetch function');
		}
	}
}
