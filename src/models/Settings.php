<?php

namespace codemonauts\instagramfeed\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var string The Instagram account to get the feed from
     */
    public $instagramUser = '';

    /**
     * @var int Timeout in seconds waiting for the Instagram page to load
     */
    public $timeout = 5;

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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['instagramUser', 'timeout', 'userAgent'], 'required'],
            ['timeout', 'double', 'min' => 1],
            ['useGuzzle', 'boolean'],
            ['dump', 'boolean'],
            ['userAgent', 'string'],
        ];
    }
}
