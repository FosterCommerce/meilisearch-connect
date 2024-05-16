<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

class Settings extends Model
{
    public string $meiliHostUrl;

    public string $meiliAdminApiKey;

    public ?string $meiliSearchUrl = null;

    public ?string $meiliSearchApiKey = null;

    /**
     * @var Index[]
     */
    public array $indices;

    public function setAttributes($values, $safeOnly = true): void
    {
        if (count($values['indices']) > 0) {
            foreach ($values['indices'] as $indexName => $indexConfig) {
                $index = new Index($indexConfig);
                $values['indices'][$indexName] = $index;
            }
        }

        parent::setAttributes($values, $safeOnly);
    }
}
