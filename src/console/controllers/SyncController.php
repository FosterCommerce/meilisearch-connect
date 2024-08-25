<?php

namespace fostercommerce\meilisearch\console\controllers;

use craft\console\Controller;
use fostercommerce\meilisearch\Plugin;
use yii\console\ExitCode;

/**
 * Sync controller
 */
class SyncController extends Controller
{
	public $defaultAction = 'all';

	public function actionSettings(): int
	{
		Plugin::getInstance()->sync->syncSettings();
		return ExitCode::OK;
	}

	public function actionAll(): int
	{
		Plugin::getInstance()->sync->syncIndices();
		return ExitCode::OK;
	}

	public function actionIndex(string $indexName): int
	{
		Plugin::getInstance()->sync->syncIndices($indexName);
		return ExitCode::OK;
	}

	public function actionRefreshAll(): int
	{
		Plugin::getInstance()->sync->refreshIndices();
		return ExitCode::OK;
	}
}
