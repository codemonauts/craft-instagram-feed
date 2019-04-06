<?php

namespace codemonauts\instagramfeed\variables;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramFeedVariable
{
    public function getFeed()
    {
        return InstagramFeed::getInstance()->instagramService->getFeed();
    }
}

