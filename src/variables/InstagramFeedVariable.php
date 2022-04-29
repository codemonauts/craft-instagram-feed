<?php

namespace codemonauts\instagramfeed\variables;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramFeedVariable
{
    public function getFeed($account = null)
    {
        return InstagramFeed::$plugin->instagramService->getFeed($account);
    }

    public function getAccount()
    {
        return InstagramFeed::$settings->getAccount();
    }
}

