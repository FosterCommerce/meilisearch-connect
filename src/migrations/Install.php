<?php

namespace fostercommerce\meilisearch\migrations;

use craft\db\Migration;
use craft\helpers\App;
use fostercommerce\meilisearch\records\Source;
use fostercommerce\meilisearch\records\SourceDependency;
use fostercommerce\meilisearch\records\TrackedDocument;

class Install extends Migration
{
	public function safeUp(): bool
	{
		App::maxPowerCaptain();

		$this->createTable(Source::tableName(), [
			'id' => $this->primaryKey(),
			'handle' => $this->string()->notNull(),
			'indexHandle' => $this->string()->notNull(),
		]);

		$this->createIndex(null, Source::tableName(), ['handle', 'indexHandle'], true);

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

		$this->createTable(TrackedDocument::tableName(), [
			'sourceId' => $this->integer()->notNull(),
			'documentId' => $this->string()->notNull(),
			'pendingDeletion' => $this->boolean()->defaultValue(false),
		]);

		$this->addPrimaryKey(null, TrackedDocument::tableName(), ['sourceId', 'documentId']);

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
		$this->dropTableIfExists(TrackedDocument::tableName());
		$this->dropTableIfExists(SourceDependency::tableName());
		$this->dropTableIfExists(Source::tableName());

		return true;
	}
}
