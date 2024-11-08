<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;

class Sync extends BaseJob
{
	public ?string $indexName = null;

	public null|string|int $identifier = null;

	public function execute($queue): void
	{
		$indices = Plugin::getInstance()->settings->getIndices($this->indexName);

		// Only get page count if we're not attempting to synchronize a specific item.
		$totalPages = $this->identifier === null
			? collect($indices)
				->reduce(
					fn ($total, Index $index): int => $total + ($index->getPageCount() ?? 0),
					0
				)
			: 0;

		$currentPage = 0;
		foreach ($indices as $index) {
			foreach (Plugin::getInstance()->sync->sync($index, $this->identifier) as $chunkSize) {
				++$currentPage;

				if ($totalPages > 0) {
					$this->setProgress($queue, $currentPage / $totalPages);
				}
			}
		}

		$this->setProgress($queue, 1);
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
