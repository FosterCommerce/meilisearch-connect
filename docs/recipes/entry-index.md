# Recipe: index entries

This index sends news entries to a Meilisearch index named `news`.

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
        'news' => IndexBuilder::fromSettings(
            IndexSettingsBuilder::create()
                ->withSearchableAttributes(['title', 'summary', 'body'])
                ->withFilterableAttributes(['category'])
                ->withSortableAttributes(['postDate'])
                ->build(),
        )
            ->withElementQuery(
                static fn (): EntryQuery => Entry::find()->section('news'),
                static fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'summary' => $entry->summary,
                    'body' => $entry->body,
                    'category' => $entry->category->one()?->title,
                    'postDate' => $entry->postDate?->format(DATE_ATOM),
                    'url' => $entry->getUrl(),
                ],
            )
            ->build(),
    ],
];
```

Then run:

```sh
php craft meilisearch-connect/sync/settings
php craft meilisearch-connect/sync/index news
```

The default auto-sync setting is on. Saved, restored, and deleted entries are kept up to date after the first sync.
