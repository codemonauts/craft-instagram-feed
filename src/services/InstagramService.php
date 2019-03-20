<?php

namespace codemonauts\instagramfeed\services;

use Craft;
use craft\base\Component;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramService extends Component
{
    public function getUserData()
    {
        $uptodate = Craft::$app->getCache()->get('instagram_uptodate');
        $cachedItems = Craft::$app->getCache()->get('instagram_data');

        if ($uptodate === false) {

            $items = $this->getInstagramData();

            if (!empty($items)) {
                Craft::$app->getCache()->set('instagram_data', $items, 2592000);
                Craft::$app->getCache()->set('instagram_uptodate', true, 21600);
                return $items;
            }
            // Future: Notify admin if empty items and cached instagram cache is too old
        }
        return $cachedItems;
    }

    private function getInstagramData()
    {
        $items = [];

        $url = sprintf("https://www.instagram.com/%s", InstagramFeed::getInstance()->getSettings()->instagramUser);
        $html = file_get_contents($url);

        if ($html !== false) {

            $arr = explode('window._sharedData = ', $html);
            $arr = explode(';</script>', $arr[1]);
            $obj = json_decode($arr[0], true);

            $mediaArray = $obj['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];

            foreach ($mediaArray as $media) {

                $item['src'] = $this->getBestPicture($media['node']['thumbnail_resources']);

                // Future: Option to Save File to S3 Check Media if not exists save media and if exists and saved add to array

                $item['likes'] = $media['node']['edge_liked_by']['count'];
                $item['comments'] = $media['node']['edge_media_to_comment']['count'];
                $item['shortcode'] = $media['node']['shortcode'];

                $items[] = $item;
            }
            return $items;
        }
        return false;
    }

    private function getBestPicture($pictures)
    {
        $url = '';
        $maxPixels = 0;

        if (is_array($pictures)) {
            foreach ($pictures as $picture) {
                $pixels = $picture['config_width'] * $picture['config_height'];

                if ($pixels > $maxPixels) {
                    $url = $picture['src'];

                    $maxPixels = $pixels;
                }
            }
        }
        return $url;
    }
}

