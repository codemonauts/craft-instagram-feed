<?php

namespace codemonauts\instagramfeed\variables;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramFeedVariable
{
    public function getUserData()
    {
        return InstagramFeed::getInstance()->instagramService->getUserData();
    }
}

