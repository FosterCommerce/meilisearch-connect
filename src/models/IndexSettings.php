<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

class IndexSettings extends Model
{
    public ?string $primaryKey = null;

    /**
     * @var string[]
     */
    public array $ranking = [];

    /**
     * @var string[]
     */
    public array $searchableAttributes = [];

    /**
     * @var string[]
     */
    public array $filterableAttributes = [];

    /**
     * @var array{maxValuesPerFacet?: int, sortFacetValuesBy?: array<non-empty-string, 'count'|'alpha'>}
     */
    public array $faceting = [];
}
