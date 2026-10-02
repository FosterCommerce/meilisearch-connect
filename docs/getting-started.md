# Getting started

Build Meilisearch indexes from any Craft element query, keep them current as content changes, and rebuild them with zero downtime, on a server you can host yourself to keep search costs down.

This walks you from `composer require` to your first search results in Twig. It uses Craft entries as an example. You can use any `ElementQueryInterface` implementation, such as entries, assets, users, or Commerce products.

## Requirements

- PHP `^8.1`
- Craft CMS `^4.6` or `^5.0`
- Meilisearch `^1.11`

## 1. Install

From the Plugin Store, search for **Meilisearch Connect** and press **Install**.

With Composer:

```sh
composer require fostercommerce/meilisearch-connect
./craft plugin/install meilisearch-connect
```

With DDEV:

```sh
ddev composer require fostercommerce/meilisearch-connect -w && ddev craft plugin/install meilisearch-connect
```

## 2. Configure an index

Create `config/meilisearch-connect.php`:

```php
<?php

use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use craft\helpers\App;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliAdminApiKey' => App::env('MEILI_ADMIN_API_KEY'),
    'meiliSearchApiKey' => App::env('MEILI_SEARCH_API_KEY'),
    'indices' => [
        'pages' => IndexBuilder::fromSettings(
            IndexSettingsBuilder::create()
                ->withSearchableAttributes(['title', 'body'])
                ->build(),
        )
            ->withElementQuery(
                static fn (): EntryQuery => Entry::find()->section('pages'),
                static fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'body' => $entry->body,
                    'url' => $entry->getUrl(),
                ],
            )
            ->build(),
    ],
];
```

`pages` is the index handle used by commands and searches. The Meilisearch index ID also defaults to `pages`.

The document must contain `id`. It is the default primary key.

## 3. Send index settings

```sh
./craft meilisearch-connect/sync/settings
```

This sends the settings defined with `IndexSettingsBuilder`.

Run this in your post-deployment commands so the settings in your Meilisearch instance match your config file.

See the [sync settings](./reference/console-commands.md#sync-settings) command.

## 4. Index the elements

```sh
./craft meilisearch-connect/sync/all
```

Each element the index's query returns becomes one or more documents, as the transformer defines. The plugin tracks them.

See the [sync data](./reference/console-commands.md#sync-data) command.

## 5. Search from Twig

```twig
{% set search = craft.meilisearch.search('pages', craft.app.request.getParam('q')) %}

{% for entry in search.results %}
  <a href="{{ entry.url }}">{{ entry.title }}</a>
{% endfor %}

{% if search.pagination.prevUrl %}
  <a href="{{ search.pagination.prevUrl }}">Previous</a>
{% endif %}

{% if search.pagination.nextUrl %}
  <a href="{{ search.pagination.nextUrl }}">Next</a>
{% endif %}
```

`search.results` holds the matching documents. Each document has the fields your transformer returned, such as `url` and `title`. After this first sync, the plugin updates documents as entries are saved, restored, and deleted.

## Where to go next

- [Transformers and dependencies](./dev-guide/transformers-and-dependencies.md), how the plugin builds documents and keeps them current.
- [Twig search](./recipes/search-twig.md), filters, sorting, and error handling.
- [Configuration reference](./reference/configuration.md), every setting and index option.
- [Console commands](./reference/console-commands.md), syncing, flushing, and refreshing indexes.
