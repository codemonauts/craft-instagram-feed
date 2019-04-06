<?php

namespace codemonauts\instagramfeed\services;

use Craft;
use craft\base\Component;

use codemonauts\instagramfeed\InstagramFeed;

class InstagramService extends Component
{
    /**
     * Returns the current Instagram feed of the configured account.
     *
     * @return array The feed data
     */
    public function getFeed()
    {
        $uptodate = Craft::$app->getCache()->get('instagram_uptodate');
        $cachedItems = Craft::$app->getCache()->get('instagram_data');
        $timeout = Craft::$app->getCache()->get('instagram_update_error');

        if ($uptodate === false && $timeout === false) {
            $items = $this->getInstagramData();

            if (!empty($items)) {
                Craft::$app->getCache()->set('instagram_data', $items, 2592000);
                Craft::$app->getCache()->set('instagram_uptodate', true, 21600);

                return $items;
            }
            if (!empty($cachedItems)) {
                // If not updated expand cache time and set update to 15min to stop from retrying every request
                Craft::$app->getCache()->set('instagram_data', $cachedItems, 2592000);
                Craft::$app->getCache()->set('instagram_update_error', true, 900);
            }
        }

        return $cachedItems;
    }

    /**
     * Fetches the feed from the public Instagram profile page.
     *
     * @return array|bool
     */
    private function getInstagramData()
    {
        $items = [];
        $user = InstagramFeed::getInstance()->getSettings()->instagramUser;
        if (empty($user)) {
            return false;
        }
        $url = sprintf("https://www.instagram.com/%s", $user);

        $html = @file_get_contents($url);

        if ($html !== false) {

            $arr = explode('window._sharedData = ', $html);
            $arr = explode(';</script>', $arr[1]);
            $obj = json_decode($arr[0], true);

            if (!array_key_exists('ProfilePage', $obj['entry_data'])) {
                return false;
            }

            $mediaArray = $obj['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];

            foreach ($mediaArray as $media) {
                $item['src'] = $this->getBestPicture($media['node']['thumbnail_resources']);
                $item['likes'] = $media['node']['edge_liked_by']['count'];
                $item['comments'] = $media['node']['edge_media_to_comment']['count'];
                $item['shortcode'] = $media['node']['shortcode'];
                $items[] = $item;
            }

            return $items;
        }

        return false;
    }

    /**
     * @param array $pictures The array of pictures to chose the best version from.
     *
     * @return string The URL of the best picture.
     */
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

