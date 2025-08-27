<?php

namespace fostercommerce\meilisearch\migrations;

use craft\helpers\App;
use craft\migrations\BaseContentRefactorMigration;
use fostercommerce\meilisearch\records\TrackedDocument;

class m250827_000000_tracked_documents extends BaseContentRefactorMigration
{
	public function safeUp(): bool
	{
		App::maxPowerCaptain();

		$this->createTable(TrackedDocument::tableName(), [
			'id' => $this->primaryKey(),
			'indexHandle' => $this->string()->notNull(),
			'sourceId' => $this->string()->notNull(),
			'documentId' => $this->string()->notNull(),
			'pendingDeletion' => $this->boolean()->defaultValue(false),
		]);

		return true;
	}

	public function safeDown(): bool
	{
		echo "m250827_000000_document_tracking_table cannot be reverted.\n";
		return false;
	}
}
