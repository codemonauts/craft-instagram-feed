<?php

namespace codemonauts\instagramfeed\models;

use craft\base\Model;

class Settings extends Model
{
    public $instagramUser = '';

    public function rules()
    {
        return [
            ['instagramUser', 'required'],
        ];
    }
}