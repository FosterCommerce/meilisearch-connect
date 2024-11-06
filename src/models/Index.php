<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;
use Generator;

/**
 * @phpstan-type FetchResult array<non-empty-string, mixed>
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
	 * @var ?callable(null|string|int, null|int): int
	 */
	private $pages;

	/**
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

	public function getPageCount(null|string|int $identifier = null): ?int
	{
		$pagesFn = $this->pages;

		if ($pagesFn === null) {
			return null;
		}

		return $pagesFn($identifier, $this->pageSize);
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
