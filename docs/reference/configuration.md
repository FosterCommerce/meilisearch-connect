# Configuration reference

Settings are in `config/meilisearch-connect.php`.

## Root settings

| Key | Default | Purpose |
| --- | --- | --- |
| `meiliHostUrl` | `null` | Meilisearch server URL. |
| `meiliAdminApiKey` | `null` | Key used for settings, indexing, and deletes. |
| `meiliSearchApiKey` | `null` | Key used for searches. Use it for storefront searches. |
| `maxDependencyRecursionLevel` | `4` | Maximum follow-up sync depth for dependencies. |
| `garbageCollectionAge` | `'1 day ago'` | Age of failed refresh indexes that Craft garbage collection can remove. Use a PHP relative date string or `null` to remove all of them. |
| `indices` | `[]` | Index definitions keyed by handle. |

## Index handles and IDs

The array key is the index handle. Use it in commands and searches. `indexId` is the index name in Meilisearch itself. If not set, `indexId` defaults to the handle.

```php
'indices' => [
    'pages' => IndexBuilder::create()
        ->withIndexId('website_pages')
        ->build(),
],
```

Use a different `indexId` in each environment when several environments share one Meilisearch server.

`.env`:

```bash
...
MEILISEARCH_PAGES_INDEX=pages_local
# MEILISEARCH_PAGES_INDEX=pages_production
```

`config/meilisearch-connect.php`:

```php
use craft\helpers\App;

'indices' => [
    'pages' => IndexBuilder::create()
        ->withIndexId(App::env('MEILISEARCH_PAGES_INDEX') ?? 'pages')
        ->build(),
],
```

## Search-only configuration

Use a search-only configuration when another service indexes data in Meilisearch, but Craft still needs to search it.

The most basic configuration is to specify the index handle. This plugin only needs to know where to look in Meilisearch to enable searching.

```php
return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliSearchApiKey' => '<Meilisearch Search Key>',
    'indices' => [
        'pages' => IndexBuilder::create()->build(),
    ],
];
```

## Full configuration

Use a full configuration when this plugin indexes data. A managed index has a fetch callback, which `withElementQuery()` sets up for you. You need an admin API key to sync its settings and documents.

### Index builder

| Method | Purpose                                                                              |
| --- |--------------------------------------------------------------------------------------|
| `withIndexId(string $id)` | Set the Meilisearch index ID.                                                        |
| `withPageSize(int $size)` | Number of source records fetched at once. Default: `100`.                            |
| `withElementQuery($query, $transformer)` | Index Craft elements.                                                                |
| `withFetchFn(callable $fetch)` | Index custom data. _Not needed if `withElementQuery` is used._                       |
| `withPagesFn(callable $pages)` | Provide progress for a custom fetch. _Not needed if `withElementQuery` is used._     |
| `withNameFn(callable $name)` | Set the source name shown in queue jobs. _Not needed if `withElementQuery` is used._ |
| `withAutoSync(bool $enabled)` | Enable or disable automatic element sync. Default: `true`.                           |
| `withActiveStatuses(array $statuses)` | Statuses that count as active during automatic sync. Default: `Element::STATUS_ENABLED` and `Entry::STATUS_LIVE`. |

When a tracked element is saved with a status that is not in `activeStatuses`, the plugin removes its documents. Automatic sync skips drafts and revisions. A user's active status is `active`, so for users pass `withActiveStatuses(['active'])`.

Use an `ElementQueryInterface` instance or a callable that returns one with `withElementQuery()`. Prefer a callable so that queries don't execute during bootstrap. This is the case when using filters such as `site($siteHandle)` which does a lookup query to get the site ID.

### Callable arguments

#### `withElementQuery($query, $transformer)`

`$query` can be an `ElementQueryInterface` or a no-argument callable that returns an `ElementQueryInterface`.

The transformer receives:

| Argument | Type | Purpose |
| --- | --- | --- |
| `$element` | `ElementInterface` | The current result from the query. Use its concrete element type in your callback. |
| `$registerDependency` | `callable(ElementInterface): void` | Call this for an element whose data is included in the document. |

Return one document array, a list of document arrays, `null`, or `[]`.

`withElementQuery()` handles the setup of `withFetchFn`, `withPagesFn`, and `withNameFn` for element indexes. You only need to use those methods for custom data or when element queries are not sufficient.

#### `withFetchFn($fetch)`

The fetch callback receives:

| Argument | Type | Purpose |
| --- | --- | --- |
| `$index` | `Index` | The configured index. |
| `$sourceHandle` | `null|string|int` | A source ID when syncing one source, otherwise `null`. |
| `$extra` | `mixed` | Always `null`. |

Return one `DocumentList`, an array of `DocumentList` objects, or a generator that yields arrays of `DocumentList` objects.

#### `withPagesFn($pages)`

The pages callback receives one argument:

| Argument | Type | Purpose |
| --- | --- | --- |
| `$index` | `Index` | The configured index. |

Return the number of expected fetch batches. This is used for queue progress.

#### `withNameFn($name)`

The name callback receives:

| Argument | Type | Purpose |
| --- | --- | --- |
| `$index` | `Index` | The configured index. |
| `$sourceHandle` | `string|int` | The source ID being synced. |
| `$extra` | `mixed` | Always `null`. |

Return a string for the queue job description.

### Meilisearch settings

Pass the array that `IndexSettingsBuilder::build()` returns to `IndexBuilder::fromSettings()`.

The builder has methods for the Meilisearch index settings: primary key, searchable, filterable, sortable, and displayed attributes; ranking rules; faceting; pagination; synonyms; typo tolerance; stop words; dictionary; tokens; and embedders.

```php
IndexSettingsBuilder::create()
    ->withPrimaryKey('id')
    ->withSearchableAttributes(['title', 'body'])
    ->withFilterableAttributes(['section'])
    ->withSortableAttributes(['postDate'])
    ->withPagination(['maxTotalHits' => 1000])
    ->build()
```

Except for `primaryKey`, the plugin resets a setting left as `null` when `sync/settings` runs. `facetSearch` and `prefixSearch` are sent and reset only on Meilisearch 1.12.0 and later. Check the [Meilisearch settings reference](https://www.meilisearch.com/docs/reference/api/settings) for valid values.
