<?php

namespace codemonauts\instagramfeed\services;

use Craft;
use craft\base\Component;
use codemonauts\instagramfeed\InstagramFeed;
use craft\helpers\FileHelper;
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
            $accountOrTag = trim(InstagramFeed::getInstance()->getSettings()->instagramUser);
            if (empty($accountOrTag)) {
                Craft::warning('No Instagram account configured.', __METHOD__);

                return [];
            }
        }

        Craft::debug('Get feed for "'.$accountOrTag.'"', __METHOD__);

        $accountOrTag = strtolower($accountOrTag);
        $hash = md5($accountOrTag);

        $uptodate = Craft::$app->getCache()->get('instagram_uptodate_' . $hash);
        $cachedItems = Craft::$app->getCache()->get('instagram_data_' . $hash);
        $timeout = Craft::$app->getCache()->get('instagram_update_error_' . $hash);

        if ($uptodate === false && $timeout === false) {
            Craft::debug('No cached data found, start fetching Instagram page.', __METHOD__);

            if (0 === strpos($accountOrTag, '#')) {
                $items = $this->getInstagramTagData($accountOrTag);
            } else {
                $items = $this->getInstagramAccountData($accountOrTag);
            }

            if (!empty($items)) {
                Craft::debug('Items found, caching them.', __METHOD__);
                Craft::$app->getCache()->set('instagram_data_' . $hash, $items, 2592000);
                Craft::$app->getCache()->set('instagram_uptodate_' . $hash, true, 21600);

                return $items;
            }

            if (!empty($cachedItems)) {
                // If not updated expand cache time and set update to 15min to stop from retrying every request
                Craft::debug('Error fetching new data from Instagram, using existing cached data and expanding cache time. Stopping requests for 15 minutes.', __METHOD__);
                Craft::$app->getCache()->set('instagram_data_' . $hash, $cachedItems, 2592000);
                Craft::$app->getCache()->set('instagram_update_error_' . $hash, true, 900);
            }
        }

        Craft::debug('Returning cached items.', __METHOD__);

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
        $html = $this->fetchInstagramPage($account);

        if (false === $html) {
            Craft::error('Instagram profile data could not be fetched. Wrong account name or not a public profile.', __METHOD__);

            return [];
        }

        $obj = $this->parseInstagramResponse($html);
        if (false === $obj) {
            return [];
        }

        if (!array_key_exists('ProfilePage', $obj['entry_data'])) {
            Craft::error('Instagram profile data could not be fetched. Maybe the site structure has changed.', __METHOD__);

            return [];
        }

        $mediaArray = $obj['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];

        return $this->flattenMediaArray($mediaArray);
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

        $path = sprintf('explore/tags/%s/', $tag);
        $html = $this->fetchInstagramPage($path);

        if (false === $html) {
            Craft::error('Instagram tag data could not be fetched.', __METHOD__);

            return [];
        }

        $obj = $this->parseInstagramResponse($html);
        if (false === $obj) {
            return [];
        }

        if (!array_key_exists('TagPage', $obj['entry_data'])) {
            Craft::error('Instagram tag data could not be fetched. Maybe the site structure has changed.', __METHOD__);

            return [];
        }

        $mediaArray = $obj['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'];

        return $this->flattenMediaArray($mediaArray);
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
     * @param string $path The path to fetch
     *
     * @return false|string
     */
    private function fetchInstagramPage($path): string
    {
        $settings = InstagramFeed::getInstance()->getSettings();

        if ($settings->useProxy && $settings->proxyKey !== '') {
            $url = 'https://igproxy.codemonauts.com/' . $path;
        } else {
            $url = 'https://www.instagram.com/' . $path;
        }

        if (!$settings->useGuzzle) {
            Craft::debug('Using php file stream to fetch Instagram page.', __METHOD__);

            $streamOptions = [
                'http' => [
                    'timeout' => $settings->timeout,
                    'header' => "Accept-Language: en-US;q=0.9,en;q=0.8\r\nUser-Agent: " . $settings->userAgent . "\r\n",
                ],
            ];

            if ($settings->useProxy && $settings->proxyKey !== '') {
                $streamOptions['http']['header'] .= 'Authorization: ' . $settings->proxyKey . "\r\n";
            }

            $context = stream_context_create($streamOptions);

            return @file_get_contents($url, false, $context);
        }

        Craft::debug('Using Guzzle to fetch Instagram page.', __METHOD__);

        $client = new Client();

        $guzzleOptions = [
            'timeout' => $settings->timeout,
            'headers' => [
                'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                'User-Agent' => $settings->userAgent,
            ],
        ];

        if ($settings->useProxy && $settings->proxyKey !== '') {
            $guzzleOptions['headers']['Authorization'] = $settings->proxyKey;
        }

        try {
            $response = $client->get($url, $guzzleOptions);
        } catch (ClientException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            return false;
        } catch (ServerException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            return false;
        }

        return $response->getBody();
    }

    /**
     * Function to parse the response body from Instagram
     *
     * @param string $response Response body from Instagram
     *
     * @return array|boolean
     */
    private function parseInstagramResponse($response)
    {
        if (InstagramFeed::getInstance()->getSettings()->dump) {
            $timestamp = time();
            $path = Craft::$app->path->getStoragePath().'/runtime/instagramfeed';
            FileHelper::writeToFile($path.'/'.$timestamp, $response);
            Craft::debug('Wrote Instagram response to '.$path.'/'.$timestamp);
        }

        Craft::debug($response, __METHOD__);

        $arr = explode('window._sharedData = ', $response);

        if (!isset($arr[1])) {
            // Check if Instagram returned a statement and not a valid page
            $response = json_decode($response);
            if (isset($response->errors)) {
                Craft::error('Instagram responsed with an error: '.implode(' ', $response->errors->error), __METHOD__);
            } else {
                Craft::error('Unknown response from Instagram. Please check debug output in devMode.', __METHOD__);
            }

            return false;
        }

        $arr = explode(';</script>', $arr[1]);
        return json_decode($arr[0], true);
    }

    /**
     * Function to flatten the Instagram response to simple array
     *
     * @param array $mediaArray The Instagram response array
     *
     * @return array
     */
    private function flattenMediaArray($mediaArray): array
    {
        $items = [];

        foreach ($mediaArray as $media) {
            $item['src'] = $this->getBestPicture($media['node']['thumbnail_resources']);
            $item['likes'] = $media['node']['edge_liked_by']['count'];
            $item['comments'] = $media['node']['edge_media_to_comment']['count'];
            $item['shortcode'] = $media['node']['shortcode'];
            $item['timestamp'] = $media['node']['taken_at_timestamp'];
            $item['caption'] = $media['node']['edge_media_to_caption']['edges'][0]['node']['text'] ?? '';
            $item['isVideo'] = (bool)$media['node']['is_video'];
            $item['video_view_count'] = $media['node']['video_view_count'] ?? 0;
            $items[] = $item;
        }

        return $items;
    }
}
