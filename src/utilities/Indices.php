<?php

namespace fostercommerce\meilisearch\utilities;

use Craft;
use craft\base\Utility;
use fostercommerce\meilisearch\Plugin;
use Meilisearch\Exceptions\ApiException;

/**
 * Indices utility
 */
class Indices extends Utility
{
	public static function displayName(): string
	{
		return Craft::t('meilisearch-connect', 'Meilisearch Connect');
	}

	public static function id(): string
	{
		return 'meilisearch-connect';
	}

	public static function iconPath(): ?string
	{
		return Craft::getAlias('@fostercommerce/meilisearch/icon-mask.svg') ?: null;
	}

	public static function contentHtml(): string
	{
		$plugin = Plugin::getInstance();

		$indices = [];

		foreach ($plugin->getSettings()->indices as $handle => $index) {
			try {
				$documentCount = $plugin->sync->getDocumentCount($index);
			} catch (ApiException $e) {
				$documentCount = 0;

				if ($e->errorCode === 'index_not_found') {
					$message = "{$e->getMessage()} Syncing the index settings should resolve this.";
				} else {
					$message = $e->getMessage();
				}
			}

			$indices[$handle] = [
				'indexId' => $index->indexId,
				'documentCount' => $documentCount,
				'message' => $message ?? null,
			];
		}

		$view = Craft::$app->getView();
		return $view->renderTemplate(
			'meilisearch-connect/utilities/indices',
			[
				'indices' => $indices,
			],
		);
	}
}
