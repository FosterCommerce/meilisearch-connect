![Meilisearch Connect](resources/img/header.png)

# Meilisearch Connect

Build Meilisearch indexes from any Craft element query, keep them current as content changes, and rebuild them with zero downtime, on a server you can host yourself to keep search costs down.

## Overview

- Index entries, assets, or Commerce products from any Craft element query, and keep the documents current as elements are saved, restored, and deleted.
- Define as many indexes as your site needs, each with its own settings and its own source, including data from outside Craft such as an external product API.
- Turn one element into several documents (an article becomes one document per paragraph), or leave it out of the index.
- Reindex an element when data it includes changes (an article when its author is saved).
- Use Meilisearch's facets, filters, sorting, ranking rules, synonyms, typo tolerance, and embedders by setting them in code and sending them with one command on deploy.
- Rebuild an index with zero downtime.
- Build search pages in Twig with filters, sorting, and pagination.

## How it works

You define each index in `config/meilisearch-connect.php` as an element query and a transformer, then run `./craft meilisearch-connect/sync/settings` and `./craft meilisearch-connect/sync/all`. After that, the plugin queues a sync in Craft's queue whenever an element the index covers is saved, restored, or deleted. A change appears in Meilisearch when the queue runs. A refresh builds a full copy of an index and swaps it in when the copy is complete.

## Usage

```php
<?php

use craft\elements\db\EntryQuery;
use craft\elements\Entry;
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

Each entry in the `pages` section becomes one document with the fields the transformer returns.

## Requirements

- PHP `^8.1`
- Craft CMS `^4.6` or `^5.0`
- Meilisearch `^1.11`

## Install

```sh
composer require fostercommerce/meilisearch-connect
./craft plugin/install meilisearch-connect
```

## Documentation

- [Getting started](https://www.fostercommerce.com/craft-cms-plugins/meilisearch-connect/docs/getting-started), install, configure an index, and run your first search
- [User guide](https://www.fostercommerce.com/craft-cms-plugins/meilisearch-connect/docs/user-guide), the Control Panel utility
- [Dev guide](https://www.fostercommerce.com/craft-cms-plugins/meilisearch-connect/docs/dev-guide), transformers, dependencies, custom data, and sync events
- [Reference](https://www.fostercommerce.com/craft-cms-plugins/meilisearch-connect/docs/reference), configuration, console commands, and the class reference
- [Recipes](https://www.fostercommerce.com/craft-cms-plugins/meilisearch-connect/docs/recipes), worked examples for entries, multiple documents, dependencies, and Twig search

## License

Proprietary

---

<a href="https://www.fostercommerce.com" target="_blank"><img src="./resources/img/foster-commerce.svg" alt="Foster Commerce" width="160" height="40"></a>
