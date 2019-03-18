<?php

namespace codemonauts\instagramfeed\models;

use craft\base\Model;

class Settings extends Model
{
    public $userId = '';
    public $accessToken = '';

    public function rules()
    {
        return [
            [['userId', 'accessToken'], 'required'],
        ];
    }
}