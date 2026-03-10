<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

class Settings extends Model
{
	/**
	 * The Meilisearch host.
	 */
	public ?string $meiliHostUrl = null;

	/**
	 * The key used when updating indices.
	 */
	public ?string $meiliAdminApiKey = null;

	/**
	 * The key used for searching against Meilisearch indices
	 */
	public ?string $meiliSearchApiKey = null;

	/**
	 * The maximum number of times a sync job can recursively spawn dependent sync jobs.
	 */
	public int $maxDependencyRecursionLevel = 4;

	/**
	 * A list of configured indices
	 *
	 * @var array<non-empty-string, Index>
	 */
	public array $indices = [];

	/**
	 * @param array{
	 *     indices: array<non-empty-string, array{
	 *         handle: non-empty-string,
	 *         indexId?: non-empty-string,
	 *         settings: array<non-empty-string, mixed>,
	 *     }>
	 * } $values
	 * @param bool $safeOnly
	 */
	public function setAttributes($values, $safeOnly = true): void
	{
		if (count($values['indices']) > 0) {
			foreach ($values['indices'] as $indexHandle => $indexConfig) {
				if (empty($indexHandle)) {
					throw new \RuntimeException('Index handle cannot be empty');
				}

				$index = new Index([
					'handle' => $indexHandle,
					...$indexConfig,
					'indexId' => $indexConfig['indexId'] ?? $indexHandle,
				]);
				$values['indices'][$indexHandle] = $index;
			}
		}

		parent::setAttributes($values, $safeOnly);
	}

	/**
	 * If $indexHandle is set, returns an `Index`. Otherwise, returns an array of indices
	 *
	 * @param bool $excludeSearchOnly when true, all search-only indexes will be excluded, unless `$indexHandle` is set.
	 * @return ($indexHandle is non-empty-string ? Index : Index[])
	 */
	public function getIndices(?string $indexHandle = null, bool $excludeSearchOnly = true): mixed
	{
		if ($indexHandle !== null) {
			$index = $this->indices[$indexHandle] ?? null;
			if (! $index instanceof Index) {
				throw new \RuntimeException("Index '{$indexHandle}' not found");
			}

			return $index;
		}

		return collect($this->indices)
			->when(
				$excludeSearchOnly,
				fn ($collection) => $collection->reject(fn (Index $index): bool => $index->isSearchOnly())
			)
			->values()
			->all();
	}
}
