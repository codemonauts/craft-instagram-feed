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
     * @var int Timeout waiting for the Instagram page to load in microseconds
     */
    public $timeout = 5000;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['instagramUser', 'timeout'], 'required'],
            ['timeout', 'integer', 'min' => 1000],
        ];
    }
}
