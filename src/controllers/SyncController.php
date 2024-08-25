<?php

namespace fostercommerce\meilisearch\controllers;

use craft\web\Controller;
use fostercommerce\meilisearch\Plugin;
use yii\web\ForbiddenHttpException;

class SyncController extends Controller
{
	public $defaultAction = 'all';

	protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

	/**
	 * @throws ForbiddenHttpException
	 */
	public function actionAll(): void
	{
		$this->requireAdmin();
		Plugin::getInstance()->sync->syncIndices();
	}
}
