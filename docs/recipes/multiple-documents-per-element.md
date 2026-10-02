# Recipe: multiple documents per element

This creates one search document for each article and tag pair. An article with three tags creates three documents.

```php
<?php

use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\db\EntryQuery;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliAdminApiKey' => '<Meilisearch Admin Key>',
    'meiliSearchApiKey' => '<Meilisearch Search Key>',
    'indices' => [
        'articles-by-tag' => IndexBuilder::fromSettings(
            IndexSettingsBuilder::create()
                ->withSearchableAttributes(['title', 'body', 'tag'])
                ->withFilterableAttributes(['tag'])
                ->build(),
        )
            ->withElementQuery(
                static fn (): EntryQuery => Entry::find()->section('articles'),
                static function (Entry $entry): array {
                    return array_map(
                        static fn (Tag $tag): array => [
                            'id' => "{$entry->id}:{$tag->id}",
                            'entryId' => $entry->id,
                            'title' => $entry->title,
                            'body' => $entry->body,
                            'tag' => $tag->title,
                            'url' => $entry->getUrl(),
                        ],
                        $entry->tags->all(),
                    );
                },
            )
            ->build(),
    ],
];
```

Replace `tags` with the handle of your tags field.

Each document needs a unique primary key. This recipe combines the entry ID and tag ID.

The plugin tracks every document ID returned for the entry. When an article loses a tag, the next sync removes that tag's old document.

Run the initial sync:

```sh
./craft meilisearch-connect/sync/settings
./craft meilisearch-connect/sync/index articles-by-tag
```
