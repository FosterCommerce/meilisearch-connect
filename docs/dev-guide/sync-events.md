# Sync events

Listen for sync events when you need to inspect or change a document batch.

## Before a batch is sent

`Sync::EVENT_BEFORE_SYNC_CHUNK` receives `SyncEvent`. Change `$event->documentLists` before the plugin writes tracking records and sends documents to Meilisearch.

```php
use Craft;
use DateTime;
use fostercommerce\meilisearch\events\SyncEvent;
use fostercommerce\meilisearch\services\Sync;
use yii\base\Event;

Event::on(
    Sync::class,
    Sync::EVENT_BEFORE_SYNC_CHUNK,
    static function (SyncEvent $event): void {
        foreach ($event->documentLists as $list) {
            foreach ($list->documents as &$document) {
                $document['indexedAt'] = (new DateTime())->format(DATE_ATOM);
            }
        }
    },
);
```

Keep the primary key in every changed document.

## After a batch is sent

`Sync::EVENT_AFTER_SYNC_CHUNK` runs after the plugin submits the batch.

```php
Event::on(
    Sync::class,
    Sync::EVENT_AFTER_SYNC_CHUNK,
    static function (SyncEvent $event): void {
        Craft::info('Sent ' . count($event->documentLists) . ' sources.', 'meilisearch-connect');
    },
);
```

The event also exposes `$event->meiliClient`.
