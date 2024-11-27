<?php

namespace fostercommerce\meilisearch\utilities;

use Craft;
use craft\base\Utility;
use fostercommerce\meilisearch\Plugin;

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
		return null;
	}

	public static function contentHtml(): string
	{
		$plugin = Plugin::getInstance();

		$indices = [];

		foreach ($plugin->getSettings()->indices as $handle => $index) {
			$documentCount = $plugin->sync->getDocumentCount($index);

			$indices[$handle] = [
				'indexId' => $index->indexId,
				'documentCount' => $documentCount,
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
