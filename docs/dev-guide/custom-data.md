# Custom data

Use `withFetchFn()` when the source is not a Craft element query.

The fetch function receives the index, an optional source handle, and extra data. Return one `DocumentList`, an array of them, or a generator that yields arrays of them.

```php
use fostercommerce\meilisearch\builders\IndexBuilder;
use fostercommerce\meilisearch\helpers\DocumentList;
use fostercommerce\meilisearch\models\Index;

'products' => IndexBuilder::create()
    ->withFetchFn(static function (Index $index, null|string|int $sourceHandle, mixed $extra): array {
        $products = ExternalProduct::all($sourceHandle);

        return array_map(
            static fn (ExternalProduct $product): DocumentList => new DocumentList(
                [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                $product->id,
            ),
            $products,
        );
    })
    ->withPagesFn(static fn (Index $index): int => 1)
    ->withNameFn(static fn (Index $index, string $sourceHandle): string => "Product {$sourceHandle}")
    ->build(),
```

The `sourceHandle` must be unique within the index. It ties a source to the documents it produces.

For large data sets, return a generator and yield small arrays of `DocumentList` objects. Each yielded array is sent as one Meilisearch batch.

```php
->withFetchFn(static function (Index $index, null|string|int $sourceHandle, mixed $extra): Generator {
    foreach (ExternalProduct::cursor() as $product) {
        yield [new DocumentList([
            'id' => $product->id,
            'name' => $product->name,
        ], $product->id)];
    }
})
```

Custom indexes do not auto-sync. Schedule `sync/index`, or queue a `Sync` job when your source data changes.

```php
use craft\helpers\Queue;
use fostercommerce\meilisearch\jobs\Sync;

Queue::push(new Sync([
    'indexHandle' => 'products',
    'sourceHandle' => $product->id,
]));
```
