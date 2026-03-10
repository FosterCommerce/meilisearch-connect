<?php

namespace fostercommerce\meilisearch\migrations;

use craft\db\Migration;
use fostercommerce\meilisearch\records\Source;
use fostercommerce\meilisearch\records\TrackedDocument;

class m260310_000000_add_unique_indexes_to_sources_and_tracked_documents extends Migration
{
	public function safeUp(): bool
	{
		$this->createIndex(null, Source::tableName(), ['handle', 'indexHandle'], true);
		$this->createIndex(null, TrackedDocument::tableName(), ['sourceId', 'documentId'], true);

		return true;
	}

	public function safeDown(): bool
	{
		echo "m260310_000000_add_unique_indexes_to_sources_and_tracked_documents cannot be reverted.\n";
		return false;
	}
}
