<?php

namespace fostercommerce\meilisearch\migrations;

use craft\helpers\App;
use craft\migrations\BaseContentRefactorMigration;
use fostercommerce\meilisearch\records\Source;
use fostercommerce\meilisearch\records\SourceDependency;
use fostercommerce\meilisearch\records\TrackedDocument;

class m250827_000000_tracked_documents extends BaseContentRefactorMigration
{
	public function safeUp(): bool
	{
		App::maxPowerCaptain();

		// == Source ==
		$this->createTable(Source::tableName(), [
			'id' => $this->primaryKey(),
			'handle' => $this->string()->notNull(),
			'indexHandle' => $this->string()->notNull(),
		]);

		// == SourceDependency ==
		$this->createTable(SourceDependency::tableName(), [
			'sourceId' => $this->integer()->notNull(),
			'parentSourceId' => $this->integer()->notNull(),
		]);

		$this->addPrimaryKey(null, SourceDependency::tableName(), [
			'sourceId',
			'parentSourceId',
		]);

		$this->addForeignKey(
			null,
			SourceDependency::tableName(),
			'sourceId',
			Source::tableName(),
			'id',
			'CASCADE',
			'CASCADE',
		);

		$this->addForeignKey(
			null,
			SourceDependency::tableName(),
			'parentSourceId',
			Source::tableName(),
			'id',
			'CASCADE',
			'CASCADE',
		);

		// == TrackedDocument ==
		$this->createTable(TrackedDocument::tableName(), [
			'id' => $this->primaryKey(),
			'sourceId' => $this->integer()->notNull(),
			'documentId' => $this->string()->notNull(),
			'pendingDeletion' => $this->boolean()->defaultValue(false),
		]);

		$this->addForeignKey(
			null,
			TrackedDocument::tableName(),
			'sourceId',
			Source::tableName(),
			'id',
			'CASCADE',
			'CASCADE',
		);

		$this->createIndex(null, TrackedDocument::tableName(), [
			'pendingDeletion',
		]);

		return true;
	}

	public function safeDown(): bool
	{
		echo "m250827_000000_document_tracking_table cannot be reverted.\n";
		return false;
	}
}
