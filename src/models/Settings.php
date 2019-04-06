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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['instagramUser', 'required'],
        ];
    }
}
