<?php

return [
    // e.g. http://localhost:7700
    'meiliHostUrl' => getenv('MEILI_HOST_URL'),
    'meiliAdminApiKey' => getenv('MEILI_ADMIN_API_KEY'),
    'meiliSearchUrl' => getenv('MEILI_SEARCH_URL'),
    'meiliSearchApiKey' => getenv('MEILI_SEARCH_API_KEY'),
    'indices' => [
        '<index name>' => [
            'settings' => [
                'ranking' => [
                    'customRanking:desc',
                    'words',
                    'exactness',
                    'proximity',
                    'attribute',
                    'date:desc',
                    'sort',
                    'typo',
                ],
                'searchableAttributes' => [
                    'title',
                ],
                'filterableAttributes' => [
                ],
                'faceting' => [
                    'maxValuesPerFacet' => 300,
                ],
            ],
            'fetch' => static fn (?int $id): array => [

            ],
        ],
    ],
];
