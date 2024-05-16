<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\Plugin;

class Delete extends BaseJob
{
    public ?string $indexName = null;

    public mixed $identifier;

    public function execute($queue): void
    {
        Plugin::getInstance()->sync->delete($this->identifier, $this->indexName, );
    }

    protected function defaultDescription(): ?string
    {
        $description = "Deleting {$this->identifier} from";
        if ($this->indexName === null) {
            return "{$description} all indices";
        }

        return "{$description} {$this->indexName}";
    }
}
