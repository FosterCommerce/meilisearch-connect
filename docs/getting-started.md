# Getting started

This uses Craft entries as an example. You can use any `ElementQueryInterface` implementation, such as entries, assets, users, or Commerce products.

## Requirements

- PHP `^8.1`
- Craft CMS `^4.6` or `^5.0`
- Meilisearch `^1.11`

## Install

From the Plugin Store, search for **Meilisearch Connect** and press **Install**.

With Composer:

```sh
composer require fostercommerce/meilisearch-connect
php craft plugin/install meilisearch-connect
```

## 1. Configure an index

Create `config/meilisearch-connect.php`:

```php
<?php

use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliAdminApiKey' => getenv('MEILI_ADMIN_API_KEY'),
    'meiliSearchApiKey' => getenv('MEILI_SEARCH_API_KEY'),
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

Use an admin key for settings and indexing. Use a search key for storefront searches.

The document must contain `id`. It is the default primary key.

## 2. Send index settings

```sh
php craft meilisearch-connect/sync/settings
```

This syncs the settings generally defined with the `IndexSettingsBuilder` builder.

It is recommended to run this as part of your post-deployment commands to ensure settings in your Meilisearch instance are up to date with the settings in your config file.

See the [sync settings](./reference/console-commands.md#sync-settings) command.

## 3. Index the elements

```sh
php craft meilisearch-connect/sync/all
```

The plugin creates and tracks one or more Meilisearch documents for each element defined by the transformer function for each index.

See the [sync data](./reference/console-commands.md#sync-data) command.

## 4. Search from Twig

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

See [Twig search](./recipes/search-twig.md) for filters and error handling.
