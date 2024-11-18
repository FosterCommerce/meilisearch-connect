<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\Plugin;
use Meilisearch\Client as MeiliClient;

trait Meili
{
	private MeiliClient $meiliClient;

	private function initMeiliClient(bool $useSearchKey): void
	{
		$settings = Plugin::getInstance()->settings;

		$url = $settings->meiliHostUrl;

		if ($url === null) {
			throw new \RuntimeException('Meilisearch host URL must be set.');
		}

		$apiKey = $useSearchKey ? $settings->meiliSearchApiKey : $settings->meiliAdminApiKey;

		if ($apiKey === null) {
			throw new \RuntimeException('Meilisearch API key must be set.');
		}

		$this->meiliClient = new MeiliClient($url, $apiKey);
	}
}
