<?php

namespace fostercommerce\meilisearch;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\web\twig\variables\CraftVariable;
use fostercommerce\meilisearch\jobs\Delete as DeleteJob;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\models\Settings;
use fostercommerce\meilisearch\services\Search;
use fostercommerce\meilisearch\services\Sync;
use fostercommerce\meilisearch\variables\Search as SearchVariable;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 *
 * @property-read Settings $settings
 * @property-read Search $search
 * @property-read Sync $sync
 */
class Plugin extends BasePlugin
{
	/**
	 * @return array<non-empty-string, mixed>
	 */
	public static function config(): array
	{
		return [
			'components' => [
				'search' => Search::class,
				'sync' => Sync::class,
			],
		];
	}

	public function init(): void
	{
		parent::init();

		if (Craft::$app->getRequest()->getIsConsoleRequest()) {
			$this->controllerNamespace = 'fostercommerce\\meilisearch\\console\\controllers';
		} else {
			$this->controllerNamespace = 'fostercommerce\\meilisearch\\controllers';
		}

		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function (Event $event): void {
			/** @var CraftVariable $variable */
			$variable = $event->sender;
			$variable->set('meilisearch', SearchVariable::class);
		});

		$settings = $this->getSettings();

		// List only indices which have been configured with an ElementQuery $query, and have $autoSync set to true.
		$autoSyncIndices = collect($settings->indices)
			->filter(static fn (Index $index): bool => $index->query instanceof ElementQuery && $index->autoSync);

		if ($autoSyncIndices->isNotEmpty()) {
			Event::on(
				Element::class,
				Element::EVENT_AFTER_SAVE,
				static function (ModelEvent $event) use ($autoSyncIndices): void {
					/** @var Element $sender */
					$sender = $event->sender;

					if (ElementHelper::isDraft($sender) || ElementHelper::isRevision($sender)) {
						// We generally don't want to index drafts or revisions.
						return;
					}

					$status = $sender->getStatus();

					// Determine which status to use to check if the element is active.
					if ($sender instanceof Entry) {
						$activeStatus = Entry::STATUS_LIVE;
					} else {
						$activeStatus = Element::STATUS_ENABLED;
					}

					if ($status === $activeStatus) {
						// If an element is active, then we should update it in the index
						$autoSyncIndices->each(static function (Index $index) use ($sender): void {
							/** @var ElementQuery<array-key, Element> $query */
							$query = $index->query;
							if ($query->id($sender->id)->exists()) {
								Queue::push(new SyncJob([
									'indexName' => $index->handle,
									'identifier' => $sender->id,
								]));
							}
						});
					} else {
						// Otherwise, we should make sure that it is not in the index
						$autoSyncIndices->each(static function (Index $index) use ($sender): void {
							/** @var ElementQuery<array-key, Element> $query */
							$query = $index->query;
							if ($query->status(null)->id($sender->id)->exists()) {
								Queue::push(new DeleteJob([
									'indexName' => $index->handle,
									'identifier' => $sender->id,
								]));
							}
						});
					}
				}
			);

			Event::on(
				Element::class,
				Element::EVENT_AFTER_SAVE,
				static function (ModelEvent $event) use ($autoSyncIndices): void {
					/** @var Element $sender */
					$sender = $event->sender;
					$autoSyncIndices->each(static function (Index $index) use ($sender): void {
						/** @var ElementQuery<array-key, Element> $query */
						$query = $index->query;
						if ($query->status(null)->id($sender->id)->exists()) {
							Queue::push(new DeleteJob([
								'indexName' => $index->handle,
								'identifier' => $sender->id,
							]));
						}
					});
				}
			);
		}
	}

	/**
	 * @throws InvalidConfigException
	 */
	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}
}
