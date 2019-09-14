<?php

namespace codemonauts\instagramfeed\services;

use Craft;
use craft\base\Component;
use codemonauts\instagramfeed\InstagramFeed;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class InstagramService extends Component
{
    /**
     * Returns the current Instagram feed of the configured account.
     *
     * @param string $accountOrTag Optional account name or hashtag to fetch. If not presented, the account from the settings will be used.
     *
     * @return array The feed data
     */
    public function getFeed(string $accountOrTag = null): array
    {
        if ($accountOrTag === null) {
            $accountOrTag = InstagramFeed::getInstance()->getSettings()->instagramUser;
            if (empty($accountOrTag)) {
                Craft::warning('No Instagram account configured.', __METHOD__);

                return [];
            }
        }

        $accountOrTag = strtolower($accountOrTag);
        $hash = md5($accountOrTag);

        $uptodate = Craft::$app->getCache()->get('instagram_uptodate_' . $hash);
        $cachedItems = Craft::$app->getCache()->get('instagram_data_' . $hash);
        $timeout = Craft::$app->getCache()->get('instagram_update_error_' . $hash);

        if ($uptodate === false && $timeout === false) {
            if ($accountOrTag[0] == '#') {
                $items = $this->getInstagramTagData($accountOrTag);
            } else {
                $items = $this->getInstagramAccountData($accountOrTag);
            }

            if (!empty($items)) {
                Craft::$app->getCache()->set('instagram_data_' . $hash, $items, 2592000);
                Craft::$app->getCache()->set('instagram_uptodate_' . $hash, true, 21600);

                return $items;
            }

            if (!empty($cachedItems)) {
                // If not updated expand cache time and set update to 15min to stop from retrying every request
                Craft::$app->getCache()->set('instagram_data_' . $hash, $cachedItems, 2592000);
                Craft::$app->getCache()->set('instagram_update_error_' . $hash, true, 900);
            }
        }

        return is_array($cachedItems) ? $cachedItems : [];
    }

    /**
     * Fetches the feed from the public Instagram profile page.
     *
     * @param string $account The account name to fetch.
     *
     * @return array
     */
    private function getInstagramAccountData(string $account): array
    {
        $items = [];

        $url = sprintf('https://www.instagram.com/%s', $account);
        $html = $this->fetchInstagramPage($url);

        if (false === $html) {
            Craft::error('Instagram profile data could not be fetched. Wrong account name or not a public profile.', __METHOD__);

            return [];
        }

        $arr = explode('window._sharedData = ', $html);
        $arr = explode(';</script>', $arr[1]);
        $obj = json_decode($arr[0], true);

        if (!array_key_exists('ProfilePage', $obj['entry_data'])) {
            Craft::error('Instagram profile data could not be fetched. Maybe the site structure has changed.', __METHOD__);

            return [];
        }

        $mediaArray = $obj['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];

        foreach ($mediaArray as $media) {
            $item['src'] = $this->getBestPicture($media['node']['thumbnail_resources']);
            $item['likes'] = $media['node']['edge_liked_by']['count'];
            $item['comments'] = $media['node']['edge_media_to_comment']['count'];
            $item['shortcode'] = $media['node']['shortcode'];
            $item['timestamp'] = $media['node']['taken_at_timestamp'];
            $item['caption'] = isset($media['node']['edge_media_to_caption']['edges'][0]['node']['text']) ? $media['node']['edge_media_to_caption']['edges'][0]['node']['text'] : '';
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Fetches the feed from the public Instagram tag page.
     *
     * @param string $tag The tag name to fetch.
     *
     * @return array
     */
    private function getInstagramTagData(string $tag): array
    {
        $tag = substr($tag, 1);

        $items = [];

        $url = sprintf('https://www.instagram.com/explore/tags/%s/', $tag);
        $html = $this->fetchInstagramPage($url);

        if (false === $html) {
            Craft::error('Instagram tag data could not be fetched.', __METHOD__);

            return [];
        }

        $arr = explode('window._sharedData = ', $html);
        $arr = explode(';</script>', $arr[1]);
        $obj = json_decode($arr[0], true);

        if (!array_key_exists('TagPage', $obj['entry_data'])) {
            Craft::error('Instagram tag data could not be fetched. Maybe the site structure has changed.', __METHOD__);

            return [];
        }

        $mediaArray = $obj['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'];

        foreach ($mediaArray as $media) {
            $item['src'] = $this->getBestPicture($media['node']['thumbnail_resources']);
            $item['likes'] = $media['node']['edge_liked_by']['count'];
            $item['comments'] = $media['node']['edge_media_to_comment']['count'];
            $item['shortcode'] = $media['node']['shortcode'];
            $item['timestamp'] = $media['node']['taken_at_timestamp'];
            $item['caption'] = isset($media['node']['edge_media_to_caption']['edges'][0]['node']['text']) ? $media['node']['edge_media_to_caption']['edges'][0]['node']['text'] : '';
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Returns the best picture in size from the Instagram result array.
     *
     * @param array $pictures The array of pictures to chose the best version from.
     *
     * @return string The URL of the best picture.
     */
    private function getBestPicture($pictures): string
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

    /**
     * Fetches the page from a given URL
     *
     * @param string $url The URL to fetch
     *
     * @return false|string
     */
    private function fetchInstagramPage($url): string
    {
        $settings = InstagramFeed::getInstance()->getSettings();

        if (!$settings->useGuzzle) {
            Craft::debug('Using php file stream to fetch Instagram page.', __METHOD__);

            $context = stream_context_create(['http' => [
                'timeout' => $settings->timeout * 1000,
                'user_agent' => $settings->userAgent,
                'headers' => [
                    'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                ],
            ]]);

            return @file_get_contents($url, false, $context);
        }

        Craft::debug('Using Guzzle to fetch Instagram page.', __METHOD__);

        $client = new Client();
        try {
            $response = $client->get($url, [
                'timeout' => $settings->timeout,
                'headers' => [
                    'User-Agent' => $settings->userAgent,
                    'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                ],
            ]);
        } catch (ClientException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            return false;
        } catch (ServerException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            return false;
        }

        return $response->getBody();
    }
}
