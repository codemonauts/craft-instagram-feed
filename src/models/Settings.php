<?php

namespace codemonauts\instagramfeed\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

class Settings extends Model
{
    /**
     * @var string The Instagram account to get the feed from
     */
    public string $instagramUser = '';

    /**
     * @var boolean Use codemonauts proxy to get the Instagram page
     */
    public bool $useProxy = false;

    /**
     * @var string The proxy key to use for authentication
     */
    public string $proxyKey = '';

    /**
     * @var int Timeout in seconds waiting for the Instagram page to load
     */
    public int $timeout = 10;

    /**
     * @var boolean Dump Instagram response in file for debugging purpose
     */
    public bool $dump = false;

    /**
     * @var boolean Use volumes to store Instagram images
     */
    public bool $useVolume = false;

    /**
     * @var string The volume handle to use for storing Instagram images
     */
    public string $volume = '';

    /**
     * @var string Subpath to use on volumes to store Instagram images
     */
    public string $subpath = '';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['instagramUser', 'timeout'], 'required'],
            ['instagramUser', 'validateInstagramUser'],
            ['timeout', 'double', 'min' => 1],
            [['useProxy', 'useVolume', 'dump'], 'boolean'],
            [['proxyKey', 'volume', 'subpath'], 'string'],
        ];
    }

    /**
     * Validator for Instagram account names
     *
     * @param string $attribute
     */
    public function validateInstagramUser(string $attribute): void
    {
        $value = $this->$attribute;

        if (str_starts_with($value, '#') || str_starts_with($value, '$')) {
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $value)) {
            $this->addError($attribute, Craft::t('instagramfeed', 'Not a valid Instagram account name.'));
        }
    }

    /**
     * Returns the parsed account.
     *
     * @return string
     */
    public function getAccount(): string
    {
        return App::parseEnv(trim($this->instagramUser));
    }
}
