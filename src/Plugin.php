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
	public string $schemaVersion = '1.8.0';

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

		// Only include indexes that have autoSync enabled.
		// It is true by default, so would need to be explicitly set to false to disable.
		// However, sync will only be triggered for an index if it has a query which is an instance of ElementQuery.
		$indexes = collect($settings->indices)->filter(static fn (Index $index): bool => $index->autoSync);

		if ($indexes->isNotEmpty()) {
			Event::on(
				Element::class,
				Element::EVENT_AFTER_SAVE,
				function (ModelEvent $event) use ($indexes): void {
					/** @var Element $sender */
					$sender = $event->sender;

					if (ElementHelper::isDraft($sender) || ElementHelper::isRevision($sender)) {
						// We generally don't want to index drafts or revisions.
						return;
					}

					$indexes->each(function (Index $index) use ($sender): void {
						$query = $index->query;

						if (is_callable($query)) {
							$query = $query();
						}

						if (! $query instanceof ElementQuery) {
							return;
						}

						if (in_array($sender->getStatus(), $index->activeStatuses, true)) {
							// Push a sync job in case this source needs to be sync'd too.
							Queue::push(new SyncJob([
								'indexHandle' => $index->handle,
								'sourceHandle' => $sender->id,
							]));
						} elseif ($query->status(null)->id($sender->id)->exists()) {
							// If this source previously was sync'd and has been disabled, we should delete it.
							if ($query->siteId !== null && $query->siteId !== $sender->siteId) {
								return;
							}

							Queue::push(new DeleteJob([
								'indexHandle' => $index->handle,
								'sourceHandle' => $sender->id,
							]));
						}
					});
				}
			);

			Event::on(
				Element::class,
				Element::EVENT_AFTER_DELETE,
				function (Event $event) use ($indexes): void {
					/** @var Element $sender */
					$sender = $event->sender;

					if (ElementHelper::isDraft($sender) || ElementHelper::isRevision($sender)) {
						return;
					}

					$indexes->each(function (Index $index) use (&$sender): void {
						// Ensure that the source is deleted if it has been previously sync'd too.
						Queue::push(new DeleteJob([
							'indexHandle' => $index->handle,
							'sourceHandle' => $sender->id,
						]));
					});
				}
			);
		}

		/** @var string $craftVersion */
		$craftVersion = Craft::$app->getVersion();

		$isCraft5 = version_compare($craftVersion, '5.0.0-RC1', '>=');
		/** @phpstan-ignore-next-line This has been renamed in later versions of Craft */
		$registerUtilitiesEvent = $isCraft5 ? Utilities::EVENT_REGISTER_UTILITIES : Utilities::EVENT_REGISTER_UTILITY_TYPES;

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
