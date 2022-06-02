<?php

namespace codemonauts\instagramfeed\services;

use Craft;
use craft\base\Component;
use codemonauts\instagramfeed\InstagramFeed;
use craft\elements\Asset;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidVolumeException;
use craft\helpers\FileHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class InstagramService extends Component
{
    public const STRUCTURE_VERSION_1 = 1;

    public const STRUCTURE_VERSION_2 = 2;

    /**
     * Returns the current Instagram feed of the configured account.
     *
     * @param string|null $accountOrTag Optional account name or hashtag to fetch. If not presented, the account from the settings will be used.
     *
     * @return array The feed data
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \craft\errors\InvalidVolumeException
     * @throws \craft\errors\VolumeException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getFeed(string $accountOrTag = null): array
    {
        $settings = InstagramFeed::getInstance()->getSettings();

        if ($accountOrTag === null) {
            $accountOrTag = $settings->getAccount();
            if (empty($accountOrTag)) {
                Craft::warning('No Instagram account configured.', __METHOD__);

                return [];
            }
        }

        Craft::debug('Get feed for "' . $accountOrTag . '"', __METHOD__);

        $accountOrTag = strtolower($accountOrTag);
        $hash = md5($settings->useVolume . $settings->volume . $settings->subpath . $accountOrTag);

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

            $items = $this->storeImages($items);

            if (!empty($items)) {
                Craft::debug('Items found, caching them.', __METHOD__);
                Craft::$app->getCache()->set('instagram_data_' . $hash, $items, 2592000);
                Craft::$app->getCache()->set('instagram_uptodate_' . $hash, true, 21600);

                return $this->populateImages($items);
            }

            if (!empty($cachedItems)) {
                // If not updated expand cache time and set update to 15min to stop from retrying every request
                Craft::debug('Error fetching new data from Instagram, using existing cached data and expanding cache time. Stopping requests for 15 minutes.', __METHOD__);
                Craft::$app->getCache()->set('instagram_data_' . $hash, $cachedItems, 2592000);
                Craft::$app->getCache()->set('instagram_update_error_' . $hash, true, 900);
            }

            if (empty($items) && empty($cachedItems)) {
                // If the cache is empty (e.g. first request ever) and the request fails, we are stopping requests for 15 minutes.
                Craft::debug('Cache is empty and no items could be fetched. Stopping requests for 15 minutes.', __METHOD__);
                Craft::$app->getCache()->set('instagram_data_' . $hash, [], 2592000);
                Craft::$app->getCache()->set('instagram_update_error_' . $hash, true, 900);

                return [];
            }
        }

        Craft::debug('Returning cached items.', __METHOD__);

        return is_array($cachedItems) ? $this->populateImages($cachedItems) : [];
    }

    /**
     * Fetches the feed from the public Instagram profile page.
     *
     * @param string $account The account name to fetch.
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function getInstagramAccountData(string $account): array
    {
        $html = $this->fetchInstagramPage($account . '/');

        if (false === $html) {
            Craft::error('Instagram profile data could not be fetched. Wrong account name or not a public profile.', __METHOD__);

            return [];
        }

        if (InstagramFeed::getInstance()->getSettings()->useProxy) {
            $obj = $this->parseProxyResponse($html);
            if (false === $obj) {
                return [];
            }

            return $this->flattenMediaArray($obj['data']['user']['edge_owner_to_timeline_media']['edges'], self::STRUCTURE_VERSION_1);
        }

        $obj = $this->parseInstagramResponse($html);
        if (false === $obj) {
            return [];
        }

        if (!array_key_exists('ProfilePage', $obj['entry_data'])) {
            if (stripos($html, 'welcome back to instagram') !== false) {
                Craft::error('Instagram tag data could not be fetched. It seems that your IP address has been blocked by Instagram. See https://github.com/codemonauts/craft-instagram-feed/issues/32', __METHOD__);
            } else {
                Craft::error('Instagram tag data could not be fetched. Maybe the site structure has changed.', __METHOD__);
            }

            return [];
        }

        return $this->flattenMediaArray($obj['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'], self::STRUCTURE_VERSION_1);
    }

    /**
     * Fetches the feed from the public Instagram tag page.
     *
     * @param string $tag The tag name to fetch.
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
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

        if (InstagramFeed::getInstance()->getSettings()->useProxy) {
            $obj = $this->parseProxyResponse($html);
            if (false === $obj) {
                return [];
            }

            return $this->flattenMediaArray($obj['data']['recent']['sections'], self::STRUCTURE_VERSION_2);
        }

        $obj = $this->parseInstagramResponse($html);
        if (false === $obj) {
            return [];
        }

        if (!array_key_exists('TagPage', $obj['entry_data'])) {
            if (stripos($html, 'welcome back to instagram') !== false) {
                Craft::error('Instagram tag data could not be fetched. It seems that your IP address has been blocked by Instagram. See https://github.com/codemonauts/craft-instagram-feed/issues/32', __METHOD__);
            } else {
                Craft::error('Instagram tag data could not be fetched. Maybe the site structure has changed.', __METHOD__);
            }

            return [];
        }

        if (isset($obj['entry_data']['TagPage'][0]['graphql'])){
            return $this->flattenMediaArray($obj['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'], self::STRUCTURE_VERSION_1);
        }

        return $this->flattenMediaArray($obj['entry_data']['TagPage'][0]['data']['recent']['sections'], self::STRUCTURE_VERSION_2);
    }

    /**
     * Returns the best picture in size from the Instagram result array.
     *
     * @param array $pictures The array of pictures to chose the best version from.
     * @param int $version The structure's version
     *
     * @return string The URL of the best picture.
     */
    private function getBestPicture(array $pictures, int $version): string
    {
        $url = '';
        $maxPixels = 0;

        if (is_array($pictures)) {
            foreach ($pictures as $picture) {
                if ($version === self::STRUCTURE_VERSION_1) {
                    $pixels = $picture['config_width'] * $picture['config_height'];
                    if ($pixels > $maxPixels) {
                        $url = $picture['src'];

                        $maxPixels = $pixels;
                    }
                } elseif ($version === self::STRUCTURE_VERSION_2) {
                    $pixels = $picture['width'] * $picture['height'];
                    if ($pixels > $maxPixels) {
                        $url = $picture['url'];

                        $maxPixels = $pixels;
                    }
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
     * @throws GuzzleException
     */
    private function fetchInstagramPage(string $path): string
    {
        $defaultUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.75 Safari/537.36';

        $settings = InstagramFeed::getInstance()->getSettings();

        if ($settings->useProxy && $settings->proxyKey !== '') {
            $url = 'https://igproxy.codemonauts.com/' . $path;
            $userAgent = $defaultUserAgent;
        } else {
            $url = 'https://www.instagram.com/' . $path;
            $userAgent = $settings->userAgent !== '' ? $settings->userAgent : $defaultUserAgent;
        }

        $client = new Client();

        $guzzleOptions = [
            'timeout' => $settings->timeout,
            'headers' => [
                'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                'User-Agent' => $userAgent,
            ],
        ];

        if ($settings->useProxy && $settings->proxyKey !== '') {
            $guzzleOptions['headers']['Authorization'] = $settings->proxyKey;
        }

        try {
            $response = $client->get($url, $guzzleOptions);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);

            return false;
        }

        return $response->getBody();
    }

    /**
     * Function to parse the response body from the proxy.
     *
     * @param string $response
     *
     * @return mixed
     */
    private function parseProxyResponse(string $response)
    {
        return json_decode($response, true);
    }

    /**
     * Function to parse the response body from Instagram
     *
     * @param string $response Response body from Instagram
     *
     * @return array|boolean
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function parseInstagramResponse(string $response)
    {
        if (InstagramFeed::getInstance()->getSettings()->dump) {
            $timestamp = time();
            $path = Craft::$app->path->getStoragePath() . '/runtime/instagramfeed';
            FileHelper::writeToFile($path . '/' . $timestamp, $response);
            Craft::debug('Wrote Instagram response to ' . $path . '/' . $timestamp);
        }

        Craft::debug($response, __METHOD__);

        $arr = explode('window._sharedData = ', $response);

        if (!isset($arr[1])) {
            // Check if Instagram returned a statement and not a valid page
            $response = json_decode($response, false);
            if (isset($response->errors)) {
                Craft::error('Instagram responsed with an error: ' . implode(' ', $response->errors->error), __METHOD__);
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
     * @param int $version The structure's version
     * @return array
     */
    private function flattenMediaArray(array $mediaArray, int $version): array
    {
        $items = [];

        if ($version === self::STRUCTURE_VERSION_1) {
            foreach ($mediaArray as $media) {
                $item['thumbnailSource'] = $this->getBestPicture($media['node']['thumbnail_resources'], $version);
                $item['imageSource'] = $media['node']['display_url'];
                $item['likes'] = $media['node']['edge_liked_by']['count'] ?? 0;
                $item['comments'] = $media['node']['edge_media_to_comment']['count'] ?? 0;
                $item['shortcode'] = $media['node']['shortcode'];
                $item['timestamp'] = $media['node']['taken_at_timestamp'];
                $item['caption'] = $media['node']['edge_media_to_caption']['edges'][0]['node']['text'] ?? '';
                $item['isVideo'] = (bool)$media['node']['is_video'];
                if ($item['isVideo']) {
                    $item['hasAudio'] = isset($media['node']['has_audio']) && $media['node']['has_audio'];
                }
                $item['video_view_count'] = $media['node']['video_view_count'] ?? 0;
                $items[] = $item;
            }
        } elseif ($version === self::STRUCTURE_VERSION_2) {
            foreach ($mediaArray as $section) {
                foreach ($section['layout_content']['medias'] as $node) {
                    if ((int)$node['media']['media_type'] === 8) {
                        if (!isset($node['media']['carousel_media'][0]['image_versions2'])) {
                            continue;
                        }
                        $item['thumbnailSource'] = $this->getBestPicture($node['media']['carousel_media'][0]['image_versions2']['candidates'], $version);
                    } else {
                        $item['thumbnailSource'] = $this->getBestPicture($node['media']['image_versions2']['candidates'], $version);
                    }
                    $item['imageSource'] = $item['thumbnailSource'];
                    $item['likes'] = $node['media']['like_count'] ?? 0;
                    $item['comments'] = $node['media']['comment_count'] ?? 0;
                    $item['shortcode'] = $node['media']['code'];
                    $item['timestamp'] = $node['media']['taken_at'];
                    $item['caption'] = $node['media']['caption']['text'] ?? '';
                    $item['isVideo'] = (int)$node['media']['media_type'] === 2;
                    if ($item['isVideo']) {
                        $item['hasAudio'] = isset($node['media']['has_audio']) && $node['media']['has_audio'];
                    }
                    $item['video_view_count'] = $node['media']['video_view_count'] ?? 0;
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * Download and store images
     *
     * @param array $items
     * @return array
     * @throws InvalidVolumeException
     * @throws GuzzleException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws \craft\errors\VolumeException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function storeImages(array $items): array
    {
        $assetsService = Craft::$app->getAssets();
        $volumesService = Craft::$app->getVolumes();

        $settings = InstagramFeed::getInstance()->getSettings();

        // Create Guzzle client
        $userAgent = $settings->userAgent !== '' ? $settings->userAgent : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.75 Safari/537.36';
        $client = new Client();
        $guzzleOptions = [
            'timeout' => $settings->timeout,
            'headers' => [
                'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                'User-Agent' => $userAgent,
            ],
        ];

        // Prepare storage informations
        $tempPath = Craft::$app->path->getTempPath();
        if ($settings->useVolume) {
            $volume = $volumesService->getVolumeByHandle($settings->volume);
            if (!$volume || ($rootFolder = $assetsService->getRootFolderByVolumeId($volume->id)) === null) {
                throw new InvalidVolumeException();
            }

            $subpath = trim($settings->subpath, '/');
            if ($subpath === '') {
                $folderId = $rootFolder->id;
            } else {
                $folder = $assetsService->findFolder([
                    'volumeId' => $volume->id,
                    'path' => $subpath . '/',
                ]);

                $folderId = $folder->id ?? $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
            }
        } else {
            $folder = Craft::$app->path->getStoragePath() . DIRECTORY_SEPARATOR . 'instagram';
            FileHelper::createDirectory($folder);
        }

        foreach ($items as $key => $item) {
            try {
                if ($settings->useVolume) {
                    $filename = FileHelper::sanitizeFilename($item['shortcode']) . '.jpg';
                    // Check if asset exists in database
                    $existingAsset = Asset::findOne(['folderId' => $folderId, 'filename' => $filename]);
                    if ($existingAsset) {
                        $items[$key]['assetId'] = $existingAsset->id;
                        continue;
                    }

                    // Check if asset exists on volume and delete it
                    if ($volume->fileExists($subpath . '/' . $filename)) {
                        $volume->deleteFile($subpath . '/' . $filename);
                    }

                    // Fetch origin and store on volume
                    $response = $client->get($item['imageSource'], $guzzleOptions);
                    $tempFilePath = $tempPath . '/' . $filename;
                    file_put_contents($tempFilePath, (string)$response->getBody());

                    $asset = new Asset();
                    $asset->tempFilePath = $tempFilePath;
                    $asset->filename = $filename;
                    $asset->newFolderId = $folderId;
                    $asset->volumeId = $volume->id;
                    $asset->avoidFilenameConflicts = false;
                    $asset->setScenario(Asset::SCENARIO_CREATE);

                    if (!Craft::$app->getElements()->saveElement($asset)) {
                        Craft::error('Could not save Instagram image to volume: ' . implode(', ', $asset->getErrorSummary(true)));
                        continue;
                    }

                    $items[$key]['assetId'] = $asset->id;
                } else {
                    // Origin image
                    $filename = $item['shortcode'] . '_full.jpg';
                    $filePath = $folder . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($filePath)) {
                        continue;
                    }
                    $response = $client->get($item['imageSource'], $guzzleOptions);
                    FileHelper::writeToFile($filePath, (string)$response->getBody());

                    // Thumbnail
                    $filename = $item['shortcode'] . '_thumb.jpg';
                    $filePath = $folder . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($filePath)) {
                        continue;
                    }
                    $response = $client->get($item['thumbnailSource'], $guzzleOptions);
                    FileHelper::writeToFile($filePath, (string)$response->getBody());
                }

            } catch (Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);

                continue;
            }
        }

        return $items;
    }

    /**
     * Adds the URLs and assets to all items
     *
     * @param array $items
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    private function populateImages(array $items): array
    {
        foreach ($items as $key => $item) {
            if (isset($item['assetId'])) {
                $asset = Asset::findOne(['id' => $item['assetId']]);
                if (!$asset) {
                    continue;
                }
                $items[$key]['asset'] = $asset;
                $items[$key]['src'] = $items[$key]['thumbnail'] = $items[$key]['image'] = $asset->getUrl();
            } else {
                $items[$key]['src'] = $items[$key]['thumbnail'] = '/instagramfeed/thumb/' . $item['shortcode'];
                $items[$key]['image'] = '/instagramfeed/image/' . $item['shortcode'];
            }
        }

        return $items;
    }
}
