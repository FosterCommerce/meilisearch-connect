<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;

class Refresh extends BaseJob
{
	public ?string $indexHandle = null;

	public function execute($queue): void
	{
		$indices = Plugin::getInstance()->settings->getIndices($this->indexHandle);

		if ($indices instanceof Index) {
			$indices = [$indices];
		}

		$indices = collect($indices);

		$totalPages = $indices->reduce(
			static fn ($total, Index $index): int => $total + ($index->getPageCount() ?? 0),
			0
		);

		$currentPage = 0;
		$indices->each(function ($index) use ($queue, $totalPages, $currentPage): void {
			foreach (Plugin::getInstance()->sync->refresh($index) as $chunkSize) {
				++$currentPage;

				if ($totalPages > 0) {
					$this->setProgress($queue, $currentPage / $totalPages);
				}
			}
		});

		$this->setProgress($queue, 1);
	}

	protected function defaultDescription(): ?string
	{
		if ($this->indexHandle === null) {
			return 'Refreshing all indices';
		}

		return "Refreshing {$this->indexHandle}";
	}
}
