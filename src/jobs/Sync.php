<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\Plugin;

class Sync extends BaseJob
{
	public ?string $indexName = null;

	public mixed $identifier = null;

	public function execute($queue): void
	{
		Plugin::getInstance()->sync->syncIndices($this->indexName, $this->identifier);
	}

	protected function defaultDescription(): ?string
	{
		if ($this->identifier !== null) {
			$description = "Sync {$this->identifier} in";
			if ($this->indexName === null) {
				return "{$description} all indices";
			}

			return "{$description} {$this->indexName}";
		}

		if ($this->indexName === null) {
			return 'Sync all indices';
		}

		return "Sync {$this->indexName}";
	}
}
