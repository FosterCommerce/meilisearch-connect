<?php

namespace fostercommerce\meilisearch;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\db\ElementQuery;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use fostercommerce\meilisearch\jobs\Delete as DeleteJob;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\models\Settings;
use fostercommerce\meilisearch\services\Search;
use fostercommerce\meilisearch\services\Sync;
use fostercommerce\meilisearch\utilities\Indices;
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

					$autoSyncIndices->each(static function (Index $index) use ($sender): void {
						/** @var ElementQuery<array-key, Element> $query */
						$query = $index->query;
						if (in_array($sender->getStatus(), $index->activeStatuses, true)) {
							// If an element is active, then we should update it in the index
							if ($query->id($sender->id)->exists()) {
								Queue::push(new SyncJob([
									'indexHandle' => $index->handle,
									'identifier' => $sender->id,
								]));
							}
						} elseif ($query->status(null)->id($sender->id)->exists()) {
							Queue::push(new DeleteJob([
								'indexHandle' => $index->handle,
								'identifier' => $sender->id,
							]));
						}
					});
				}
			);

			Event::on(
				Element::class,
				Element::EVENT_AFTER_DELETE,
				static function (Event $event) use ($autoSyncIndices): void {
					/** @var Element $sender */
					$sender = $event->sender;
					$autoSyncIndices->each(static function (Index $index) use ($sender): void {
						/** @var ElementQuery<array-key, Element> $query */
						$query = $index->query;
						if ($query->status(null)->id($sender->id)->exists()) {
							Queue::push(new DeleteJob([
								'indexHandle' => $index->handle,
								'identifier' => $sender->id,
							]));
						}
					});
				}
			);
		}

		/** @var string $craftVersion */
		$craftVersion = Craft::$app->getVersion();

		$registerUtilitiesEvent = version_compare($craftVersion, '5.0.0-RC1', '>=')
			? Utilities::EVENT_REGISTER_UTILITIES
			/** @phpstan-ignore-next-line This has been renamed in later versions of Craft */
			: Utilities::EVENT_REGISTER_UTILITY_TYPES;

		Event::on(
			Utilities::class,
			$registerUtilitiesEvent,
			static function (RegisterComponentTypesEvent $event): void {
				$event->types[] = Indices::class;
			}
		);
	}

	/**
	 * @throws InvalidConfigException
	 */
	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}
}
