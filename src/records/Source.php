<?php

namespace fostercommerce\meilisearch\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

/**
 * A Source represents a single, identifiable source of information in a specific Meilisearch index.
 * @property int $id
 * @property string $handle The Craft element ID of the source, stored as a string.
 * @property string $indexHandle The handle of the Meilisearch index this source belongs to.
 */
class Source extends ActiveRecord
{
	public static function tableName(): string
	{
		return '{{%meilisearch_connect_sources}}';
	}

	public function getChildSources(): ActiveQuery
	{
		return $this->hasMany(
			self::class,
			[
				'id' => 'sourceId',
			],
		)->viaTable(
			SourceDependency::tableName(),
			[
				'parentSourceId' => 'id',
			],
		);
	}

	public function getParentSources(): ActiveQuery
	{
		return $this
			->hasMany(
				self::class,
				[
					'id' => 'parentSourceId',
				],
			)->viaTable(
				SourceDependency::tableName(),
				[
					'sourceId' => 'id',
				],
			);
	}

	public function getTrackedDocuments(): ActiveQuery
	{
		return $this->hasMany(TrackedDocument::class, [
			'sourceId' => 'id',
		]);
	}

	public function detectCyclicDependency(int $parentSourceId): bool
	{
		// A cyclic dependency will be where the parent's parent is the current source
		return SourceDependency::find()
			->where([
				'sourceId' => $parentSourceId,
				'parentSourceId' => $this->id,
			])
			->exists();
	}

	/**
	 * @return ($createIfMissing is true ? self : self|null)
	 */
	public static function get(string $indexHandle, string $sourceHandle, bool $createIfMissing = false): ?self
	{
		$sourceIdentifier = [
			'indexHandle' => $indexHandle,
			'handle' => $sourceHandle,
		];

		/** @var Source|null $source */
		$source = self::findOne($sourceIdentifier);

		if ($createIfMissing && $source === null) {
			$source = new self($sourceIdentifier);
			$source->save();
		}

		return $source;
	}
}
