<?php

use craft\elements\Entry;
use craft\elements\db\ElementQuery;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
	/**
	 * The host URL used when communicating with the Meilisearch instance.
	 */
	'meiliHostUrl' => 'http://localhost:7700',
	/**
	 * Meilisearch Admin API key used for updating index settings and syncing index data.
	 */
	'meiliAdminApiKey' => '<Meilisearch Admin Key>',
	/**
	 * Meilisearch Search API key used when performing searches using the plugins search service or with the Twig variable.
	 */
	'meiliSearchApiKey' => '<Meilisearch Search Key>',
	/**
	 * A list of indices that can be created and/or searched.
	 */
	'indices' => [
		/**
		 * The key here is the index handle you would use when running commands against a specific index.
		 *
		 * This means you can always refer to the same index handle in code and in commands.
		 *
		 * For example:
		 *
		 * ```bash
		 * craft meilisearch-connect/sync/flush pages
		 * craft meilisearch-connect/sync/index pages
		 * ```
		 */
		'pages' => IndexBuilder::fromSettings( // Use IndexBuilder::create() for search-only config.
			IndexSettingsBuilder::create()
				// Optionally configure ranking rules
				// See https://www.meilisearch.com/docs/reference/api/settings#ranking-rules
				->withRanking([
					'customRanking:desc',
					'words',
					'exactness',
					'proximity',
					'attribute',
					'date:desc',
					'sort',
					'typo',
				])
				// Optionally configure searchable attributes
				// See https://www.meilisearch.com/docs/reference/api/settings#searchable-attributes
				->withSearchableAttributes([
					'id',
					'title',
					'section',
				])
				// Optionally configure faceting
				// See https://www.meilisearch.com/docs/reference/api/settings#faceting-object for options
				->withFaceting([
					'maxValuesPerFacet' => 300,
				])
				->build()
		)
			// Index ID is the actual ID of the index _in_ Meilisearch. This
			// would normally be an environment variable if the ID is expected
			// to be different across environments.
			->withIndexId('pages')
			// Set your element/entry query and include a transform function which will be applied to every
			// item in the query.
			->withElementQuery(
				static function(): ElementQuery {
					return Entry::find();
				}, // Get all entries
				// Return false from this transform function to prevent this item from being indexed.
				static fn (Entry $entry): array => [
					// Transform the entry
					'id' => $entry->id,
					'title' => $entry->title,
					'section' => $entry->section->handle ?? '',
					'url' => $entry->getUrl(),
					// Any other fields you want to index
				]
			)
			// Turn the configuration into an array that Craft can use to configure the plugin.
			->build(),
	],
];
