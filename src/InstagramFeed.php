<?php

namespace codemonauts\instagramfeed;

use codemonauts\instagramfeed\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use yii\base\Event;
use codemonauts\instagramfeed\services\InstagramService;
use codemonauts\instagramfeed\variables\InstagramFeedVariable;

class InstagramFeed extends Plugin
{
    public $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->setComponents([
            'instagramService' => InstagramService::class,
        ]);

        // Register template variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('instagram', InstagramFeedVariable::class);
        });

        // Register site routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['instagramfeed/image/<shortCode:[^\/]+>'] = 'instagramfeed/image/image';
            $event->rules['instagramfeed/thumb/<shortCode:[^\/]+>'] = 'instagramfeed/image/thumb';
        });
    }

    /**
     * @inheritDoc
     */
    public function afterInstall()
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
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml()
    {
        $volumeOptions = [];
        $volumes = Craft::$app->getVolumes()->getPublicVolumes();
        foreach ($volumes as $volume) {
            $volumeOptions[$volume->handle] = $volume->name;
        }

        return Craft::$app->getView()->renderTemplate('instagramfeed/settings', [
                'settings' => $this->getSettings(),
                'volumeOptions' => $volumeOptions,
            ]
        );
    }
}
