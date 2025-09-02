<?php

namespace fostercommerce\meilisearch\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

/**
 * A Source represents a single, identifiable source of information in a specific Meilisearch index.
 * @property int $id
 * @property string $handle
 * @property string $indexHandle
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
				'id' => 'parentSourceId',
			],
		)->viaTable(
			SourceDependency::tableName(),
			[
				'sourceId' => 'id',
			],
		);
	}

	public function getParentSources(): ActiveQuery
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

	public function getTrackedDocuments(): ActiveQuery
	{
		return $this->hasMany(TrackedDocument::class, [
			'sourceId' => 'id',
		]);
	}

	public static function getOrCreate(string $indexHandle, string $sourceHandle): self
	{
		$sourceIdentifier = [
			'indexHandle' => $indexHandle,
			'handle' => $sourceHandle,
		];

		/** @var Source|null $source */
		$source = self::findOne($sourceIdentifier);

		if ($source === null) {
			$source = new self($sourceIdentifier);
			$source->save();
		}

		return $source;
	}
}
