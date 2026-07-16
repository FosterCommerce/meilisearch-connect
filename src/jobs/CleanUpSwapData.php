<?php

namespace fostercommerce\meilisearch\jobs;

use craft\queue\BaseJob;
use DateTime;
use fostercommerce\meilisearch\Plugin;

class CleanUpSwapData extends BaseJob
{
	public ?string $age = null;

	public function execute($queue): void
	{
		$before = null;
		if ($this->age !== null && $this->age !== '') {
			$before = new DateTime($this->age);
		}

		Plugin::getInstance()->sync->cleanUpSwapIndexes($before);
		$this->setProgress($queue, 1);
	}

	protected function defaultDescription(): ?string
	{
		return "Cleaning up swap data older than {$this->age}";
	}
}
