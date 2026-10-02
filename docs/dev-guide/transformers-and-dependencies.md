# Transformers and dependencies

A transformer turns each result from an `ElementQueryInterface` into one or more Meilisearch documents. Entries are only one example. You can use any Craft element query.

```php
->withElementQuery(
    static fn (): EntryQuery => Entry::find()->section('articles'),
    static function (Entry $entry, callable $registerDependency): array {
        $author = $entry->author;

        if ($author !== null) {
            $registerDependency($author);
        }

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'authorName' => $author?->fullName,
        ];
    },
)
```

## Return values

Return one associative array for one document.

```php
['id' => $entry->id, 'title' => $entry->title]
```

Return a list of associative arrays for several documents from one source.

```php
[
    ['id' => "{$entry->id}:en", 'title' => $entry->title],
    ['id' => "{$entry->id}:fr", 'title' => $entry->titleFr],
]
```

Return `null` or `[]` when the source should have no documents. Existing documents for that source are removed on the next sync.

Every document needs the configured primary key. The default key is `id`.

## What the plugin tracks

The plugin stores three things for each managed index:

- A source: the Craft element ID or custom source handle.
- The document IDs created from that source.
- Dependencies registered by the transformer.

When a source changes, the plugin compares its new document IDs with the old ones. It adds new documents and deletes document IDs no longer returned by the transformer.

## Register a dependency

Call `$registerDependency()` for an element whose data appears in the source document.

When that element is saved or deleted, the plugin queues a sync for the source element. In the example above, changing an author reindexes every tracked article that registered that author.

Only register elements that affect the document. Do not register the source element itself. Dependency syncs need the index's auto-sync setting on, which is the default.

Dependency sync follows at most `maxDependencyRecursionLevel` levels of dependents. The default is `4`. Direct two-way loops are skipped.

See [Multiple documents per element](../recipes/multiple-documents-per-element.md), [Split an element into content documents](../recipes/split-element-documents.md), and [Related element dependencies](../recipes/related-element-dependencies.md) for full examples.
