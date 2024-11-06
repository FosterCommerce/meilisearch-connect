<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

class Settings extends Model
{
	public string $meiliHostUrl;

	public string $meiliAdminApiKey;

	public ?string $meiliSearchUrl = null;

	public ?string $meiliSearchApiKey = null;

	/**
	 * @var array<non-empty-string, Index>
	 */
	public array $indices;

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
	 * @return array<int, Index>
	 */
	public function getIndices(?string $indexName = null): array
	{
		if ($indexName !== null) {
			$result = array_filter([
				$this->indices[$indexName] ?? null,
			]);

			if ($result === []) {
				throw new \RuntimeException("Index '{$indexName}' not found");
			}

			return $result;
		}

		return array_values($this->indices);
	}
}
