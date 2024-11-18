# Meilisearch Connect

Meilisearch Connect enables seamless data synchronization between Craft CMS and Meilisearch, allowing your Craft entries
and custom fields to be indexed and searched efficiently.

## Requirements

This plugin requires Craft CMS 4.6.0 or later, and PHP 8.1 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “meilisearch”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require fostercommerce/craft-meilisearch

# tell Craft to install the plugin
./craft plugin/install meilisearch
```

## Configuration

### With a meilisearch-connect.php config file

The easiest way to configure the plugin using the config file is to use the `IndexBuilder` and `IndexSettingsBuilder`
utility classes.

Take a look at the example [config.php](src/config.php) file.

## Usage

### Ensuring data is up-to-date

There are two recommended ways to keep data in Meilisearch indices up-to-date with your data in Craft:

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

## Console Commands

### `meilisearch-connect/sync/settings`

Synchronize settings for all indices.

**Note** that this should be run whenever the `settings` key for an index changes. Running this after a deployment is
usually a good idea.

### `meilisearch-connect/sync/index <index name>`

Synchronize data for the given index

### `meilisearch-connect/sync/all (default)`

Synchronizes data to all indices.

### `meilisearch-connect/sync/flush <index name>`

Flushes data for the given index.

### `meilisearch-connect/sync/flush-all`

Flushes data for all indices.

### `meilisearch-connect/sync/refresh-all`

Flush and synchronize data for all indices.

## Search

### From Twig

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

