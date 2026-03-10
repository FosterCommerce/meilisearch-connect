<?php

namespace fostercommerce\meilisearch;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\db\ElementQuery;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use fostercommerce\meilisearch\jobs\Delete as DeleteJob;
use fostercommerce\meilisearch\jobs\Sync as SyncJob;
use fostercommerce\meilisearch\models\Index;
use fostercommerce\meilisearch\models\Settings;
use fostercommerce\meilisearch\records\Source;
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
	public string $schemaVersion = '1.9.0';

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
			$handleElementSaved = static function (Event $event) use ($indexes): void {
				/** @var Element $sender */
				$sender = $event->sender;

				if (ElementHelper::isDraft($sender) || ElementHelper::isRevision($sender)) {
					// We generally don't want to index drafts or revisions.
					return;
				}

				$indexes->each(function (Index $index) use ($sender): void {
					$isTracked = Source::get($index->handle, (string) $sender->id) instanceof Source;
					$isActive = in_array($sender->getStatus(), $index->activeStatuses, true);

					if ($isTracked) { // It exists
						if ($isActive) { // And it is tracked
							Queue::push(new SyncJob([
								// So we resync it
								'indexHandle' => $index->handle,
								'sourceHandle' => $sender->id,
							]));
						} else {
							Queue::push(new DeleteJob([
								// Otherwise we delete it
								'indexHandle' => $index->handle,
								'sourceHandle' => $sender->id,
							]));
						}

						return;
					}

					if (! $isActive) {
						return;
					}

					// At this point, it's likely a new element, and we don't know where to sync it to, so we'll sync it to every index as long as
					// it's matched by that index's query.
					$query = $index->query;

					if (is_callable($query)) {
						$query = $query();
					}

					if (! $query instanceof ElementQuery) {
						return;
					}

					// If it exists in the index query, then we can sync it.
					if ($query->status(null)->id($sender->id)->exists()) {
						Queue::push(new SyncJob([
							'indexHandle' => $index->handle,
							'sourceHandle' => $sender->id,
						]));
					}
				});
			};

			Event::on(
				Element::class,
				Element::EVENT_AFTER_RESTORE,
				$handleElementSaved,
			);

			Event::on(
				Element::class,
				Element::EVENT_AFTER_SAVE,
				$handleElementSaved,
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

					$indexes->each(function (Index $index) use ($sender): void {
						if (! Source::get($index->handle, (string) $sender->id) instanceof Source) {
							// We only want to update the index if this source already exists, either as a parent or a dependency.
							return;
						}

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
