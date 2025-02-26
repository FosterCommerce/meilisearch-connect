# Meilisearch Connect

Meilisearch Connect enables seamless data synchronization between Craft CMS and Meilisearch, allowing your Craft entries
and custom fields to be indexed and searched efficiently.

## Requirements

- PHP >=8.1.0
- CraftCMS ^4.6.0|^5.0.0
- Meilisearch ^1.11.0

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “meilisearch”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# Go to the project directory
cd /path/to/my-project.test

# Tell Composer to load the plugin
composer require fostercommerce/craft-meilisearch

# Tell Craft to install the plugin
php craft plugin/install meilisearch
```

## Configuration

This plugin can be configured using a regular associative array. Take a look at the [Settings](src/models/Settings.php) class to see all the possible configuration options.

A more convenient method to configuring the plugin is to use the builder classes provided.

These classes allow you to provide just the values necessary to configure your indices, and they provide type-hinting and documentation to make it easier to configure.

### Index handles and index IDs

When configuring the `indices` array in the plugin config file, each index is represented by a handle. This handle is the _key_ in the associative array.

For example:

```php
'indices' => [
	'pages' => // ...
	'products' => // ...
]
```

_Meilisearch_ uses Index IDs for each index.

By default, Meilisearch Connect will use the handle give for an index as it's Index ID in Meilisearch.

If you'd like use a different ID, you can instead specify the ID yourself by calling `withIndexId`:

```php
'indices' => [
	'pages' => IndexBuilder::create()->withIndexId('pages_index')->build(),
	'products' => IndexBuilder::create()->withIndexId('products_index')->build(),
]
```

This is particularly useful if you want to segment indices on shared Meilisearch instances for dev or staging environments.

For example, if you had a staging environment which hosted a number of sites and their respective indices, you can set an environment variable of your choosing to set the Index ID:

```bash
# .env
MEILISEARCH_PAGES_INDEX=mysite_staging_pages
MEILISEARCH_PRODUCTS_INDEX=mysite_staging_products
```

```php
// config.php:
'indices' => [
	'pages' => IndexBuilder::create()
		->withIndexId(App::env('MEILISEARCH_PAGES_INDEX') ?? 'pages')
		->build(),
	'products' => IndexBuilder::create()
		->withIndexId(App::env('MEILISEARCH_PRODUCTS_INDEX') ?? 'products')
		->build(),
]
```

### [Search-only configuration](src/config.search-only.php)

Search-only configuration is useful if you're syncing data to Meilisearch from somewhere other than Craft, but you'd like to search that data from Craft.

The most basic configuration is to simply specify the index handle. This plugin only needs to know where to look in Meilisearch to enable searching.

#### Example configuration

```php
return [
	'meiliHostUrl' => 'http://localhost:7700',
	'meiliSearchApiKey' => '<Meilisearch Search Key>',
	'indices' => [
		'pages' => IndexBuilder::create()->build(),
	],
];
```

### [Full configuration](src/config.php)

When using a full configuration, the `IndexSettingsBuilder` can be used to assist with configuring the _settings_ for the index.

To see what the various index settings options are, have a look at Meilisearch [API reference](https://www.meilisearch.com/docs/reference/api/settings).

#### Element queries

If you're planning on indexing elements, such as Entry's, you can make use of the `withElementQuery` builder method.

This method takes an element query, and a transform function.

The element query can be any implementation of `ElementQuery`. 

The transform function receives items from the result of the query and must return an associative array. If the transform function returns a [falsey](https://www.php.net/manual/en/function.empty.php) value, then the item will be skipped from indexing.

Set the `pageSize` using `withPagesSize` to configure how many elements should be indexed at a time when synchronizing an entire index.

#### Custom data

If you're indexing non-standard data, i.e. anything that isn't an `ElementQuery`, you can implement your own `fetch` and `pages` functions.

`fetch` is a function which returns a [`Generator`](https://www.php.net/manual/en/language.generators.syntax.php) or an array.

If your `fetch` returns a Generator, it is useful to also set the `pages`. The `pages` function which takes the current `Index` and returns the total amount of pages expected to be returned for that index.

This is useful when synchronizing all data for an index using a queue job.

#### Example configuration

```php
return [
	'meiliHostUrl' => 'http://localhost:7700',
	'meiliAdminApiKey' => '<Meilisearch Admin Key>',
	'meiliSearchApiKey' => '<Meilisearch Search Key>',
	'indices' => [
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
				])
				->withFaceting([
					'maxValuesPerFacet' => 300,
				])
				->build()
		)
			->withElementQuery(
				Entry::find(), // Get all entries
				static fn (Entry $entry): array => [
					// Transform the entry
					'id' => $entry->id,
					'title' => $entry->title,
					'section' => $entry->section->handle ?? '',
					'url' => $entry->getUrl(),
					// Any other fields you want to include in indexed content
				]
			)
			->build(),
	],
];
```

## Usage

### Ensuring data is up-to-date

#### Automatically

Indices which have `autoSync` set to true, _and_ are using an `ElementQuery`, will automatically synchronize data to their respective indices on Meilisearch.

##### Active check

The plugin will check whether the element is active before decided whether to update it in the index. 

If it is active, it'll will either update or create the item in Meilisearch.

Otherwise, it will delete the item from Meilisearch.

The default active statuses are `Element::STATUS_ENABLED` and `Entry::STATUS_LIVE`. However, different element types can have different active statuses. For example, in Craft Commerce, a `Product` also has `STATUS_LIVE`.

In this case the `activeStatuses` array should be set for the index to indicate which values you're expecting to indicate that an element is active.

#### Manually

Auto sync will not work if you have one or more of the following config:
- `autoSync` is false;
- `query` is not an instance of `ElementQuery` or a subclass of that.

In this case, you have two recommended options to keep data in Meilisearch indices up-to-date with your data in Craft:
- Running the sync/all or sync/index commands on a schedule, for example, via crontab, or
- If you're indexing Craft Elements or Entries, updating the data for that item using a save or delete event.

#### `Element::EVENT_AFTER_SAVE`

```php
Event::on(
	Product::class,
	Element::EVENT_AFTER_SAVE,
		static function (\craft\events\ModelEvent $event) {
		if (
			! ElementHelper::isDraft($event->sender) &&
			! $event->sender->resaving &&
			! ElementHelper::isRevision($event->sender)
		) {
			$item = $event->sender;
			$status = $item->getStatus();
			if ($status === Entry::STATUS_LIVE) {
				// If an entry is live, then we can add it to the index
				Queue::push(new SyncJob([
					'indexName' => 'pages',
					'identifier' => $item->id,
				]));
			} else {
				// Otherwise, we should make sure that it is not in the index
				Queue::push(new DeleteJob([
					'indexName' => 'pages',
					'identifier' => $item->id,
				]));
			}
		}
	}
);
```

#### `Element::EVENT_AFTER_DELETE`

```php
Event::on(
	Product::class,
	Element::EVENT_AFTER_DELETE,
	static function (\craft\events\Event $event) {
		$item = $event->sender;
		Queue::push(new DeleteJob([
			'identifier' => $item->id,
		]));
	}
);
```

### Search

#### From Twig

This plugin exposes a Twig Variable which can be used to search against your Meilisearch instance.

```twig
{% set searchResults = craft.meilisearch.search('my-index', query, {'hitsPerPage': 25}) %}
```

`search` takes four arguments. The index handle, your search query, search parameters, and an options array.

The search parameters are [extra parameters](https://www.meilisearch.com/docs/reference/api/search#body) which are sent with the search request to Meilisearch.

Options are additional options to pass to [meilisearch-php](https://github.com/meilisearch/meilisearch-php).

The return value is an associative array with the following keys:
- `results`: An array of results containing the data for each record returned from Meilisearch.
- `pagination`: A standard Craft pagination instance.


### Console Commands

#### `meilisearch-connect/sync/settings`

Synchronize settings for all indices.

**Note** that this should be run whenever the `settings` key for an index changes. Running this after a deployment is
usually a good idea.

#### `meilisearch-connect/sync/index <index name>`

Synchronize data for the given index

#### `meilisearch-connect/sync/all (default)`

Synchronizes data to all indices.

#### `meilisearch-connect/sync/flush <index name>`

Flushes data for the given index.

#### `meilisearch-connect/sync/flush-all`

Flushes data for all indices.

#### `meilisearch-connect/sync/refresh-all`

Flush and synchronize data for all indices.
