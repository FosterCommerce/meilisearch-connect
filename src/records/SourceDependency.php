<?php

namespace fostercommerce\meilisearch\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

/**
 * @property string $sourceId
 * @property string $parentSourceId
 */
class SourceDependency extends ActiveRecord
{
	public static function tableName(): string
	{
		return '{{%meilisearch_connect_source_dependencies}}';
	}

	public function getSource(): ActiveQuery
	{
		return $this->hasOne(Source::class, [
			'sourceId' => 'id',
		]);
	}

	public function getParentSource(): ActiveQuery
	{
		return $this->hasOne(Source::class, [
			'parentSourceId' => 'id',
		]);
	}
}
