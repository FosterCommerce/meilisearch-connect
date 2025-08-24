<?php

namespace fostercommerce\meilisearch\events;

use craft\base\Event;
use fostercommerce\meilisearch\models\Index;

/**
 * @phpstan-import-type FetchResult from Index
 */
class SyncEvent extends Event
{
	/**
	 * @var FetchResult
	 */
	public $chunk;
}
