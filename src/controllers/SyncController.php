<?php

namespace fostercommerce\meilisearch\controllers;

use craft\helpers\Queue;
use craft\web\Controller;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;
use yii\web\ForbiddenHttpException;

class SyncController extends Controller
{
	public $defaultAction = 'all';

	protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

	public function actionIndex(): void
	{
		$this->requireAdmin();
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		Queue::push(new SyncJob([
			'indexHandle' => $handle,
		]));
		$this->setSuccessFlash("Sync job for {$handle} added to the queue.");
	}

	public function actionSettings(): void
	{
		$this->requireAdmin();
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		$plugin = Plugin::getInstance();
		/** @var Index $index */
		$index = $plugin->getSettings()->getIndices($handle);
		$plugin->sync->syncSettings($index);
		$this->setSuccessFlash("Index settings for {$handle} updated.");
	}

	public function actionFlush(): void
	{
		$this->requireAdmin();
		/** @var string $handle */
		$handle = $this->request->getRequiredParam('handle');
		$plugin = Plugin::getInstance();
		/** @var Index $index */
		$index = $plugin->getSettings()->getIndices($handle);
		$plugin->sync->flush($index);
		$this->setSuccessFlash("Flushed index {$handle}.");
	}

	/**
	 * @throws ForbiddenHttpException
	 */
	public function actionAll(): bool
	{
		$this->requireAdmin();
		Queue::push(new SyncJob());
		return true;
	}
}
