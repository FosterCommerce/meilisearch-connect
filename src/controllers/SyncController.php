<?php

namespace fostercommerce\meilisearch\controllers;

use craft\helpers\Queue;
use craft\web\Controller;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use yii\web\ForbiddenHttpException;

class SyncController extends Controller
{
	public $defaultAction = 'all';

	protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

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
