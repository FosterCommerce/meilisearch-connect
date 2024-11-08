<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;
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
	 * An optional callable that is used by the plugin when reporting progress on synchronization tasks.
	 *
	 * Example:
	 *
	 * ```php
	 * static function (?int $pageSize): int {
	 *   return ceil(Entry::find()->count() / ($pageSize ?? 100));
	 * }
	 * ```
	 *
	 * @var ?callable(null|int): int
	 */
	private $pages;

	/**
	 * A callback used to fetch data that should be synchronized to the index in a Meilisearch instance.
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
	 * static function (?int $id, ?int $pageSize): array {
	 *   return collect(Entry::find()->all())
	 *     ->map(static fn ($entry) => [
	 *       'id' => $entry->id,
	 *       'title' => $entry->title,
	 *       'description' => $entry->description,
	 *     ]);
	 * }
	 * ```
	 *
	 * @var callable(null|string|int, null|int): FetchCallableReturn
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
	 * @param callable(null|string|int): int $pages
	 */
	public function setPages(callable $pages): void
	{
		$this->pages = $pages;
	}

	/**
	 * @param callable(null|string|int): FetchCallableReturn $fetch
	 */
	public function setFetch(callable $fetch): void
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
		$pagesFn = $this->pages;

		if ($pagesFn === null) {
			return null;
		}

		return $pagesFn($this->pageSize);
	}

	/**
	 * @return Generator<int, FetchResult>
	 */
	public function execFetchFn(null|string|int $identifier = null): Generator
	{
		$fetchFn = $this->fetch;
		$result = $fetchFn($identifier, $this->pageSize);

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
