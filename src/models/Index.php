<?php

namespace fostercommerce\meilisearch\models;

use craft\base\Model;

class Index extends Model
{
	public ?IndexSettings $settings = null;

	/**
	 * @var callable|array|string
	 */
	public $fetch;

	public function __construct($config = [])
	{
		if (is_array($config['settings']) && $config['settings'] !== []) {
			$config['settings'] = new IndexSettings($config['settings']);
		}

		parent::__construct($config);
	}

	public function init(): void
	{
		parent::init();

		if (! $this->settings instanceof \fostercommerce\meilisearch\models\IndexSettings) {
			$this->settings = new IndexSettings();
		}
	}
}
