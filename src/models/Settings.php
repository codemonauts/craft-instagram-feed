<?php

namespace codemonauts\instagramfeed\models;

use codemonauts\instagramfeed\InstagramFeed;
use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * @var string The Instagram account to get the feed from
     */
    public $instagramUser = '';

    /**
     * @var boolean Use codemonauts proxy to get the Instagram page
     */
    public $useProxy = false;

    /**
     * @var string The proxy key to use for authentication
     */
    public $proxyKey = '';

    /**
     * @var int Timeout in seconds waiting for the Instagram page to load
     */
    public $timeout = 10;

    /**
     * @var boolean Use Guzzle instead of php's file stream to fetch the Instagram page
     */
    public $useGuzzle = false;

    /**
     * @var boolean Dump Instagram response in file for debugging purpose
     */
    public $dump = false;

    /**
     * @var string User Agent to use when fetching the Instagram page
     */
    public $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.75 Safari/537.36';

    /**
     * @var boolean Use volumes to store Instagram images
     */
    public $useVolume = false;

    /**
     * @var string The volume handle to use for storing Instagram images
     */
    public $volume = '';

    /**
     * @var string Subpath to use on volumes to store Instagram images
     */
    public $subpath = '';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['instagramUser', 'timeout', 'userAgent'], 'required'],
            ['timeout', 'double', 'min' => 1],
            [['useGuzzle', 'useProxy', 'useVolume'], 'boolean'],
            ['dump', 'boolean'],
            [['userAgent', 'proxyKey', 'volume', 'subpath'], 'string'],
        ];
    }

    public function getAccount()
    {
        return Craft::parseEnv(trim(InstagramFeed::getInstance()->getSettings()->instagramUser));
    }
}
