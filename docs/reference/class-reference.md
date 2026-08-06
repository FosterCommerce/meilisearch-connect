# Class reference

## `IndexBuilder`

`fostercommerce\meilisearch\builders\IndexBuilder`

Builds one index configuration.

| Method | Purpose |
| --- | --- |
| `create()` | Start a search-only index. |
| `fromSettings(array $settings)` | Start a managed index with Meilisearch settings. |
| `withIndexId(string $id)` | Set the Meilisearch index ID. |
| `withPageSize(int $size)` | Set the source batch size. |
| `withElementQuery(ElementQueryInterface|callable $query, callable $transformer)` | Fetch and transform Craft elements. |
| `withFetchFn(?callable $fetch)` | Fetch custom data. |
| `withPagesFn(?callable $pages)` | Set custom fetch progress. |
| `withNameFn(?callable $name)` | Set the name shown in queue jobs. |
| `withAutoSync(bool $enabled = true)` | Enable or disable automatic element sync. |
| `withActiveStatuses(array $statuses)` | Set active statuses for automatic sync. |
| `build()` | Return the config array. |

See [Transformers and dependencies](../dev-guide/transformers-and-dependencies.md) and [Custom data](../dev-guide/custom-data.md).

## `IndexSettingsBuilder`

`fostercommerce\meilisearch\builders\IndexSettingsBuilder`

Builds Meilisearch index settings for `IndexBuilder::fromSettings()`.

`create()` starts the builder. `build()` returns the settings array.

Settings methods:

- `withPrimaryKey()`
- `withSearchableAttributes()`
- `withFilterableAttributes()`
- `withSortableAttributes()`
- `withDisplayedAttributes()`
- `withRanking()`
- `withFaceting()`
- `withPagination()`
- `withSynonyms()`
- `withDictionary()`
- `withDistinctAttribute()`
- `withProximityPrecision()`
- `withFacetSearch()`
- `withPrefixSearch()`
- `withSearchCutoffMs()`
- `withSeparatorTokens()`
- `withNonSeparatorTokens()`
- `withStopWords()`
- `withTypoTolerance()`
- `withEmbedders()`

See the [configuration reference](./configuration.md) for an example and Meilisearch's settings documentation for accepted values.

## `Search`

`fostercommerce\meilisearch\services\Search`

Access it through `Plugin::getInstance()->search`.

```php
$result = Plugin::getInstance()->search->search(
    'news', // Index handle
    'summer sale', // Search query
    ['hitsPerPage' => 12], // Search params
);
```

`search(string $indexHandle, string $query, array $searchParams = [], array $options = [])` returns a Meilisearch `SearchResult`.

It throws `Meilisearch\Exceptions\ApiException` if the request fails.

## `Sync`

`fostercommerce\meilisearch\services\Sync`

Access it through `Plugin::getInstance()->sync`.

| Method | Purpose |
| --- | --- |
| `syncSettings(Index $index)` | Create the index and apply its configured settings. |
| `sync(Index $index, ?string $sourceHandle)` | Sync one source or every source. Returns a generator of document counts. |
| `delete(Index $index, string $sourceHandle)` | Delete documents created by one source. |
| `flush(Index $index)` | Delete every document and tracking record. |
| `refresh(Index $index)` | Build, sync, and swap a temporary index. Returns a generator of document counts. |
| `cleanUpSwapIndexes(?DateTime $before = null, ?string $prefix = null)` | Delete temporary refresh indexes. Returns the number deleted. |
| `getDocumentCount(Index $index)` | Return the remote document count. |

`Sync::EVENT_BEFORE_SYNC_CHUNK` and `Sync::EVENT_AFTER_SYNC_CHUNK` are the available events. See [Sync events](../dev-guide/sync-events.md).

## Jobs

Queue jobs are in `fostercommerce\meilisearch\jobs`.

| Class | Main properties | Purpose |
| --- | --- | --- |
| `Sync` | `indexHandle`, `sourceHandle`, `dependencyRecursionLevel` | Sync one source or one or all indexes. |
| `Delete` | `indexHandle`, `sourceHandle` | Delete one source from one or all indexes. |
| `Refresh` | `indexHandle` | Refresh one or all managed indexes. |
| `CleanUpSwapData` | `age` | Remove old temporary refresh indexes. |

```php
use craft\helpers\Queue;
use fostercommerce\meilisearch\jobs\Sync;

Queue::push(new Sync([
    'indexHandle' => 'news',
    'sourceHandle' => 42,
]));
```

## `SyncEvent`

`fostercommerce\meilisearch\events\SyncEvent`

Event handlers receive:

| Property | Purpose |
| --- | --- |
| `documentLists` | The current batch of `DocumentList` objects. Change this before a sync batch is sent. |
| `meiliClient` | The Meilisearch client used for the sync. |
