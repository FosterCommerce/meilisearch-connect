<?php

namespace fostercommerce\meilisearch\services;

use fostercommerce\meilisearch\Plugin;
use Meilisearch\Client as MeiliClient;

trait Meili
{
	private MeiliClient $meiliClient;

	private function initMeiliClient(): void
	{
		$settings = Plugin::getInstance()->settings;
		$this->meiliClient = new MeiliClient($settings->meiliHostUrl, $settings->meiliAdminApiKey);
	}
}
