<?php

namespace fostercommerce\meilisearch\controllers;

use craft\helpers\Queue;
use craft\web\Controller;
use fostercommerce\meilisearch\jobs\Refresh as RefreshJob;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;

class SyncController extends Controller
{
	public $defaultAction = 'index-all';

	protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

	public function actionIndex(): void
	{
		$this->requireAdmin(false);
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		Queue::push(new SyncJob([
			'indexHandle' => $handle,
		]));
		$this->setSuccessFlash("Sync job for {$handle} added to the queue.");
	}

	public function actionIndexAll(): void
	{
		$this->requireAdmin(false);
		Queue::push(new SyncJob());
		$this->setSuccessFlash('Job added to the queue to sync all indices.');
	}

	public function actionRefresh(): void
	{
		$this->requireAdmin(false);
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		Queue::push(new RefreshJob([
			'indexHandle' => $handle,
		]));
		$this->setSuccessFlash("Refresh job for {$handle} added to the queue.");
	}

	public function actionRefreshAll(): void
	{
		$this->requireAdmin(false);
		Queue::push(new RefreshJob());
		$this->setSuccessFlash('Job added to the queue to refresh all indices.');
	}

	public function actionSettings(): void
	{
		$this->requireAdmin(false);
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		$plugin = Plugin::getInstance();
		/** @var Index $index */
		$index = $plugin->getSettings()->getIndices($handle);
		$plugin->sync->syncSettings($index);
		$this->setSuccessFlash("Index settings for {$handle} updated.");
	}

	public function actionSettingsAll(): void
	{
		$this->requireAdmin(false);
		$plugin = Plugin::getInstance();
		/** @var Index[] $indices */
		$indices = $plugin->getSettings()->getIndices();
		foreach ($indices as $index) {
			$plugin->sync->syncSettings($index);
		}

		$this->setSuccessFlash('All index settings have been updated.');
	}

	public function actionFlush(): void
	{
		$this->requireAdmin(false);
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		$plugin = Plugin::getInstance();
		/** @var Index $index */
		$index = $plugin->getSettings()->getIndices($handle);
		$plugin->sync->flush($index);
		$this->setSuccessFlash("Flushed index {$handle}.");
	}

	public function actionFlushAll(): void
	{
		$this->requireAdmin(false);
		$plugin = Plugin::getInstance();
		/** @var Index[] $indices */
		$indices = $plugin->getSettings()->getIndices();
		foreach ($indices as $index) {
			$plugin->sync->flush($index);
		}

		$this->setSuccessFlash('All indices have been flushed.');
	}
}
