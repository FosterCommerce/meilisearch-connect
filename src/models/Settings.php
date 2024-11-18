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
	 * @return ($indexHandle is non-empty-string ? Index : Index[])
	 */
	public function getIndices(?string $indexHandle = null): mixed
	{
		if ($indexHandle !== null) {
			$result = array_filter([
				$this->indices[$indexHandle] ?? null,
			]);

			if ($result === []) {
				throw new \RuntimeException("Index '{$indexHandle}' not found");
			}

			return $result[0];
		}

		return array_values($this->indices);
	}
}
