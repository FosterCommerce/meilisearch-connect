<?php

namespace fostercommerce\meilisearch\events;

use craft\base\Event;
use fostercommerce\meilisearch\helpers\DocumentList;
use Meilisearch\Client;

class SyncEvent extends Event
{
	/**
	 * @var DocumentList[]
	 */
	public array $documentLists;

	public Client $meiliClient;
}
