<?php
/**
 * Craft3 Instagram Feed plugin for Craft CMS 3.x
 *
 * A Twig extension for CraftCMS (Craft3.x) that helps to get your own feed data.
 *
 * @link      https://www.codemonauts.com
 * @copyright Copyright (c) 2019 Codemonauts
 */

namespace codemonauts\instagramfeed;

use Craft;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;


class Plugin extends craft\base\Plugin
{
    // Static Properties
    // =========================================================================

    public $hasCpSettings = true;

    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '0.1';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'instagramfeed',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
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
            [ 'settings' => $this->getSettings() ]
        );
    }


}
