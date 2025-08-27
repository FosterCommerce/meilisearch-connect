<?php

namespace fostercommerce\meilisearch\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

/**
 * @property string $indexHandle The handle of the index the document is in
 * @property string $sourceId The ID of the Source this document was generated from
 * @property string $documentId The document ID in the Meilisearch index
 * @property bool $pendingDeletion Whether this document is marked for deletion. See Sync::sync.
 */
class TrackedDocument extends ActiveRecord
{
	public static function tableName(): string
	{
		return '{{%meilisearch_connect_tracked_documents}}';
	}

	public function getSource(): ActiveQuery
	{
		return $this->hasOne(Source::class, [
			'id' => 'sourceId',
		]);
	}
}
