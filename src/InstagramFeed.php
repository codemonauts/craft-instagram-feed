<?php

namespace codemonauts\instagramfeed;

use codemonauts\instagramfeed\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
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

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('instagram', InstagramFeedVariable::class);
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
        return Craft::$app->getView()->renderTemplate('instagramfeed/settings', [
                'settings' => $this->getSettings()
            ]
        );
    }
}
