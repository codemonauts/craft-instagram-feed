<?php

namespace codemonauts\instagramfeed\variables;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramFeedVariable
{
    public function getFeed($account = null)
    {
        return InstagramFeed::getInstance()->instagramService->getFeed($account);
    }

    public function getAccount()
    {
        return InstagramFeed::getInstance()->getSettings()->getAccount();
    }
}

