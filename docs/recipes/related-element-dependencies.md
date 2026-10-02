# Recipe: reindex entries when an author changes

An article document includes its author's name and bio. Register the author as a dependency so a saved author queues each related article for reindexing.

```php
<?php

use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use craft\elements\User;
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\builders\IndexSettingsBuilder;

return [
    'meiliHostUrl' => 'http://localhost:7700',
    'meiliAdminApiKey' => '<Meilisearch admin key>',
    'meiliSearchApiKey' => '<Meilisearch search key>',
    'indices' => [
        'articles' => IndexBuilder::fromSettings(
            IndexSettingsBuilder::create()
                ->withSearchableAttributes(['title', 'body', 'authorName', 'authorBio'])
                ->build(),
        )
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
                        'body' => $entry->body,
                        'authorName' => $author?->fullName,
                        'authorBio' => $author?->bio,
                    ];
                },
            )
            ->build(),
    ],
];
```

The plugin records the article as the parent source and the author as its dependency.

When the author is saved, the plugin queues a sync for each tracked article that uses that author. When the author is deleted, it does the same before removing the dependency.

This only works after the articles have been indexed at least once.
