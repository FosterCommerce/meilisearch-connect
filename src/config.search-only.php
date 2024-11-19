<?php

use fostercommerce\meilisearch\builders\IndexBuilder;

return [
	/**
	 * The host URL used when communicating with the Meilisearch instance.
	 */
	'meiliHostUrl' => 'http://localhost:7700',
	/**
	 * Meilisearch Search API key used when performing searches using the plugins search service or with the Twig variable.
	 */
	'meiliSearchApiKey' => '<Meilisearch Search Key>',
	/**
	 * A list of indices that can be searched.
	 */
	'indices' => [
		/**
		 * The key here is the index handle you would use when running commands against a specific index.
		 *
		 * This means you can always refer to the same index handle when searching using the plugins search service or the Twig variable.
		 */
		'pages' => IndexBuilder::create()
			// Index ID is the actual ID of the index _in_ Meilisearch. This
			// would normally be an environment variable if the ID is expected
			// to be different across environments.
			->withIndexId('pages')
			// Turn the configuration into an array that Craft can use to configure the plugin.
			->build(),
	],
];
