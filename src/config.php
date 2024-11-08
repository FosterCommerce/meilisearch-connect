<?php

use craft\elements\Entry;
use craft\helpers\App;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

/** @var string $pagesIndexId */
$pagesIndexId = App::env('MEILI_PAGES_INDEX');

return [
	// e.g. http://localhost:7700
	'meiliHostUrl' => getenv('MEILI_HOST_URL'),
	'meiliAdminApiKey' => getenv('MEILI_ADMIN_API_KEY'),
	'meiliSearchApiKey' => getenv('MEILI_SEARCH_API_KEY'),
	'indices' => [
		/**
		 * The key here is the index name you would use when running commands against a specific index.
		 *
		 * This means you can always refer to the same index name in code and in commands.
		 *
		 * For example:
		 *
		 * ```bash
		 * craft meilisearch-connect/sync/flush pages
		 * craft meilisearch-connect/sync/index pages
		 * ```
		 */
		'pages' => IndexBuilder::fromSettings(
			IndexSettingsBuilder::create()
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
				->withSearchableAttributes([
					'id',
					'title',
					'section',
					'keywords',
				])
				->withFaceting([
					'maxValuesPerFacet' => 300,
				])
				->build()
		)
			// Index ID is the actual ID of the index _in_ Meilisearch. This would normally change across environments.
			->withIndexId($pagesIndexId)
			->withElementQuery(
				Entry::find()->section(['not', 'makes', 'models']),
				static function (Entry $entry): array {
					/** @var string $keywords */
					/** @phpstan-ignore-next-line Example text field name "keywords" */
					$keywords = $entry->keywords;
					$keywords = collect(explode(',', $keywords))->map(static fn ($keyword): string => trim($keyword));

					return [
						'id' => $entry->id,
						'title' => $entry->title,
						'section' => $entry->section->handle ?? '',
						'keywords' => $keywords,
						'url' => $entry->getUrl(),
						// Other fields
					];
				}
			)
			->build(),
	],
];
