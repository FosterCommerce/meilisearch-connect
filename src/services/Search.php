<?php

namespace fostercommerce\meilisearch\services;

use yii\base\Component;

class Search extends Component
{
    use Meili;

    public function init(): void
    {
        $this->initMeiliClient();
    }

    public function search(string $indexName, string $query, array $searchParams = [], array $options = []): mixed
    {
        $result = $this->meiliClient
            ->index($indexName)
            ->search($query, $searchParams, $options);

        $offset = ($result->getHitsPerPage() * ($result->getPage() - 1));

        return [
            'results' => $result->getHits(),
            'pagination' => [
                'first' => $offset + 1,
                'last' => $offset + count($result->getHits()),
                'total' => $result->getTotalHits(),
                'currentPage' => $result->getPage(),
                'totalPages' => $result->getTotalPages(),
            ],
        ];
    }
}
