<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\Plugin;

class Delete extends BaseJob
{
	public ?string $indexName = null;

	public string|int $identifier;

	public function execute($queue): void
	{
		$indices = Plugin::getInstance()->settings->getIndices($this->indexName);

		foreach ($indices as $i => $index) {
			Plugin::getInstance()->sync->delete($index, $this->identifier);
			$this->setProgress($queue, $i / count($indices));
		}
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
