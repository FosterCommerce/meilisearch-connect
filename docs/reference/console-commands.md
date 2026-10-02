# Console commands

Commands skip search-only indexes unless a specific search-only handle is supplied. Syncing or flushing a search-only index does not change it.

## Sync settings

```sh
./craft meilisearch-connect/sync/settings
```

Creates each managed Meilisearch index and applies its configured settings.

Run this after changing index settings.

## Sync data

```sh
./craft meilisearch-connect/sync/all
./craft meilisearch-connect/sync/index pages
```

`all` is the default action. Neither command removes sources that no longer match the configured query. Use refresh when you need a full replacement.

## Flush data

```sh
./craft meilisearch-connect/sync/flush pages
./craft meilisearch-connect/sync/flush-all
```

Deletes all documents from the index and removes the plugin's tracking records.

## Refresh all indexes

```sh
./craft meilisearch-connect/sync/refresh-all
```

Builds a temporary index, indexes the current data, then swaps it with the live index. Use this to replace an entire index.
