<?php
/**
 * Craft3 Instagram Feed plugin for Craft CMS 3.x
 *
 * @link      https://www.codemonauts.com
 * @copyright Copyright (c) 2019 Codemonauts
 */

namespace codemonauts\instagramfeed;

use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

use codemonauts\instagramfeed\services\InstagramService;
use codemonauts\instagramfeed\variables\InstagramFeedVariable;


class InstagramFeed extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public $hasCpSettings = true;
    public $schemaVersion = '0.1';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'instagramService' => InstagramService::class,
        ]);

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('instagram', InstagramFeedVariable::class);
        });

        Craft::info(
            Craft::t(
                'instagramfeed',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function afterInstall ()
    {
        parent::afterInstall();

        if (Craft::$app->getRequest()->getIsConsoleRequest())
            return;

        Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl('settings/plugins/instagramfeed')
        )->send();
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new \codemonauts\instagramfeed\models\Settings();
    }

    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'instagramfeed/settings',
            ['settings' => $this->getSettings()]
        );
    }
}
