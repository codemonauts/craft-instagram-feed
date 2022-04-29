<?php

namespace codemonauts\instagramfeed;

use codemonauts\instagramfeed\models\Settings;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\utilities\ClearCaches;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use yii\base\Event;
use codemonauts\instagramfeed\services\InstagramService;
use codemonauts\instagramfeed\variables\InstagramFeedVariable;

/**
 * @property InstagramService $instagramService
 */
class InstagramFeed extends Plugin
{
    /**
     * @var \codemonauts\instagramfeed\InstagramFeed|null
     */
    public static ?InstagramFeed $plugin;

    /**
     * @var \codemonauts\instagramfeed\models\Settings|null
     */
    public static ?Settings $settings;

    /**
     * @inheritDoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        self::$settings = self::$plugin->getSettings();

        $this->setComponents([
            'instagramService' => InstagramService::class,
        ]);

        // Register template variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            $variable = $event->sender;
            $variable->set('instagram', InstagramFeedVariable::class);
        });

        // Register site routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['instagramfeed/image/<shortCode:[^\/]+>'] = 'instagramfeed/image/image';
            $event->rules['instagramfeed/thumb/<shortCode:[^\/]+>'] = 'instagramfeed/image/thumb';
        });

        // Register Cache Tag Option
        if (Craft::$app->getRequest()->isCpRequest) {
            Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_TAG_OPTIONS, function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'tag' => self::$plugin->instagramService::CACHE_TAG,
                    'label' => Craft::t('instagramfeed', 'Instagram data'),
                ];
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function afterInstall(): void
    {
        parent::afterInstall();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl('settings/plugins/instagramfeed')
        )->send();
    }

    /**
     * @inheritDoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml(): ?string
    {
        // Get all volumes
        $volumeOptions = [];
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        foreach ($volumes as $volume) {
            $volumeOptions[$volume->handle] = $volume->name;
        }

        // Get config file
        $configFile = Craft::$app->getConfig()->getConfigFromFile('instagramfeed');

        return Craft::$app->getView()->renderTemplate('instagramfeed/settings', [
                'settings' => $this->getSettings(),
                'volumeOptions' => $volumeOptions,
                'configFile' => $configFile,
            ]
        );
    }
}
