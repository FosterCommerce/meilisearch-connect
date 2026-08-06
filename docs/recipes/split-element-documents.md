# Recipe: split an element into content documents

This splits each article's content into paragraphs. Each paragraph becomes a document. Meilisearch uses `entryId` to return only the best matching paragraph from each article.

The example uses entries, but the same pattern works with any Craft element query.

```php
<?php

use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliAdminApiKey' => '<Meilisearch Admin Key>',
    'meiliSearchApiKey' => '<Meilisearch Search Key>',
    'indices' => [
        'articles' => IndexBuilder::fromSettings(
            IndexSettingsBuilder::create()
                ->withDistinctAttribute('entryId')
                ->withSearchableAttributes(['title', 'content'])
                ->build(),
        )
            ->withElementQuery(
                static fn (): EntryQuery => Entry::find()->section('articles'),
                static function (Entry $entry): array {
                    $parts = explode('\n', $entry->content);

                    return array_map(
                        static fn (string $content, int $index): array => [
                            'entryId' => $entry->id,
                            'id' => "{$entry->id}-{$index}",
                            'title' => $entry->title,
                            'url' => $entry->getUrl(),
                            'content' => $content,
                        ],
                        $parts,
                        array_keys($parts),
                    );
                },
            )
            ->build(),
    ],
];
```

Replace `content` with your field handle.

`id` must be unique for every content part. `entryId` stays the same for every document from the same entry.

`withDistinctAttribute('entryId')` makes Meilisearch return one result for each entry. It chooses the best matching content document, but the result still contains the matching `content` value.

When the entry content changes, the plugin compares the returned IDs with the tracked IDs. It adds new parts and removes parts that no longer exist.

Run the initial sync:

```sh
php craft meilisearch-connect/sync/settings
php craft meilisearch-connect/sync/index articles
```
