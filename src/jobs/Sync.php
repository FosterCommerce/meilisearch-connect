<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\Plugin;

class Sync extends BaseJob
{
	public ?string $indexHandle = null;

	public null|string|int $identifier = null;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['indexName'])) {
			// Index name is deprecated
			$config['indexHandle'] = $config['indexName'];
			unset($config['indexName']);
		}

		parent::__construct($config);
	}

	public function execute($queue): void
	{
		$indices = Plugin::getInstance()->settings->getIndices($this->indexHandle);

		if ($indices instanceof Index) {
			$indices = [$indices];
		}

		$indices = collect($indices);

		// Only get page count if we're not attempting to synchronize a specific item.
		$totalPages = $this->identifier === null
			? $indices->reduce(static fn ($total, Index $index): int => $total + ($index->getPageCount() ?? 0), 0)
			: 0;

		$currentPage = 0;
		$indices->each(function ($index) use ($queue, $totalPages, &$currentPage): void {
			foreach (Plugin::getInstance()->sync->sync($index, $this->identifier) as $chunkSize) {
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
		if ($this->identifier !== null) {
			$description = "Sync {$this->identifier} in";
			if ($this->indexHandle === null) {
				return "{$description} all indices";
			}

			return "{$description} {$this->indexHandle}";
		}

		if ($this->indexHandle === null) {
			return 'Sync all indices';
		}

		return "Sync {$this->indexHandle}";
	}
}
