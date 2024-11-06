<?php

namespace fostercommerce\meilisearch\console\controllers;

use craft\console\Controller;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Exceptions\TimeOutException;
use yii\console\ExitCode;

class SyncController extends Controller
{
	public $defaultAction = 'all';

	/**
	 * @throws TimeOutException
	 */
	public function actionSettings(): int
	{
		$indices = Plugin::getInstance()->settings->getIndices();

		foreach ($indices as $index) {
			Plugin::getInstance()->sync->syncSettings($index);
			$this->stdout("Synchronized settings for {$index->handle}");
		}

		return ExitCode::OK;
	}

	public function actionAll(): int
	{
		$indices = Plugin::getInstance()->settings->getIndices();

		foreach ($indices as $index) {
			foreach (Plugin::getInstance()->sync->sync($index, null) as $chunkSize) {
				$this->stdout("Synchronized {$chunkSize} documents to {$index->handle}" . PHP_EOL);
			}
		}

		return ExitCode::OK;
	}

	public function actionIndex(string $indexName): int
	{
		$index = Plugin::getInstance()->settings->getIndices($indexName)[0] ?? null;

		if ($index === null) {
			throw new \RuntimeException("Index {$indexName} does not exist.");
		}

		foreach (Plugin::getInstance()->sync->sync($index, null) as $chunkSize) {
			$this->stdout("Synchronized {$chunkSize} documents to {$index->handle}" . PHP_EOL);
		}

		return ExitCode::OK;
	}

	public function actionFlush(string $indexName): int
	{
		$index = Plugin::getInstance()->settings->getIndices($indexName)[0] ?? null;

		if ($index === null) {
			throw new \RuntimeException("Index {$indexName} does not exist.");
		}

		Plugin::getInstance()->sync->flush($index);
		$this->stdout("Flushed all documents from {$index->handle}" . PHP_EOL);

		return ExitCode::OK;
	}

	public function actionFlushAll(): int
	{
		$indices = Plugin::getInstance()->settings->getIndices();

		foreach ($indices as $index) {
			Plugin::getInstance()->sync->flush($index);
			$this->stdout("Flushed all documents from {$index->handle}" . PHP_EOL);
		}

		return ExitCode::OK;
	}

	public function actionRefreshAll(): int
	{
		$syncService = Plugin::getInstance()->sync;
		$indices = Plugin::getInstance()->settings->getIndices();

		foreach ($indices as $index) {
			$syncService->flush($index);
			$this->stdout("Flushed all documents from {$index->handle}" . PHP_EOL);
			foreach ($syncService->sync($index, null) as $chunkSize) {
				$this->stdout("Synchronized {$chunkSize} documents to {$index->handle}" . PHP_EOL);
			}
		}

		return ExitCode::OK;
	}
}
