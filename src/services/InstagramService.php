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
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use yii\caching\TagDependency;

class InstagramService extends Component
{
    public const CACHE_TAG = 'instagramfeed';

    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.75 Safari/537.36';

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
        /**
         * @var \codemonauts\instagramfeed\models\Settings $settings
         */
        $settings = InstagramFeed::getInstance()->getSettings();

        // Cache and cache tag
        $cacheService = Craft::$app->getCache();
        $dependency = new TagDependency();
        $dependency->tags[] = self::CACHE_TAG;

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

        $uptodate = $cacheService->get('instagram_uptodate_' . $hash);
        $cachedItems = $cacheService->get('instagram_data_' . $hash);
        $timeout = $cacheService->get('instagram_update_error_' . $hash);

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
                $cacheService->set('instagram_data_' . $hash, $items, 2592000, $dependency);
                $cacheService->set('instagram_uptodate_' . $hash, true, 21600, $dependency);
                $cacheService->set('instagram_update_error_' . $hash, false, 0, $dependency);

                return $this->populateImages($items);
            }

            if (!empty($cachedItems)) {
                // If not updated expand cache time and set update to six hours to stop from retrying every request
                Craft::debug('Error fetching new data from Instagram, using existing cached data and expanding cache time. Stopping requests for 15 minutes.', __METHOD__);
                $cacheService->set('instagram_data_' . $hash, $cachedItems, 2592000, $dependency);
                $cacheService->set('instagram_update_error_' . $hash, true, 21600, $dependency);
            }

            if (empty($cachedItems)) {
                // If the cache is empty (e.g. first request ever) and the request fails, we are stopping requests for some time.
                $waitTime = $this->canUseProxy() ? 10 : 900;
                Craft::debug('Cache is empty and no items could be fetched. Stopping requests for ' . $waitTime . ' seconds.', __METHOD__);
                $cacheService->set('instagram_data_' . $hash, [], 2592000, $dependency);
                $cacheService->set('instagram_update_error_' . $hash, true, $waitTime, $dependency);

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
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getInstagramAccountData(string $account): array
    {
        $html = $this->fetchInstagramAccount($account);

        if (null === $html) {
            Craft::error('Instagram profile data could not be fetched.', 'instagramfeed');

            return [];
        }

        $obj = $this->parseResponse($html);

        return $this->extractMedia($obj);
    }

    /**
     * Fetches the feed from the public Instagram tag page.
     *
     * @param string $tag The tag name to fetch.
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getInstagramTagData(string $tag): array
    {
        $html = $this->fetchInstagramTag($tag);

        if (null === $html) {
            Craft::error('Instagram tag data could not be fetched.', 'instagramfeed');

            return [];
        }

        $obj = $this->parseResponse($html);

        return $this->extractMedia($obj);
    }

    /**
     * Fetches an account from Instagram.
     *
     * @param string $account The account name to fetch.
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function fetchInstagramAccount(string $account): ?string
    {
        $settings = InstagramFeed::getInstance()->getSettings();
        $cookies = new CookieJar();
        $client = new Client([
            'cookies' => $cookies,
        ]);

        $guzzleOptions = [
            'timeout' => $settings->timeout,
            'headers' => [
                'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                'User-Agent' => self::DEFAULT_USER_AGENT,
            ],
        ];

        try {
            if ($this->canUseProxy()) {
                $url = sprintf('https://igproxy.codemonauts.com/%s/', $account);
                $referer = Craft::$app->getSites()->getCurrentSite()->getBaseUrl();
                $guzzleOptions['headers']['Authorization'] = $settings->proxyKey;
                $guzzleOptions['headers']['Referer'] = $referer;
                $guzzleOptions['headers']['X-Plugin-Version'] = InstagramFeed::getInstance()->getVersion();
                $response = $client->get($url, $guzzleOptions);
            } else {
                $url = sprintf('https://www.instagram.com/%s/', $account);
                $client->get($url, $guzzleOptions);
                $guzzleOptions['headers']['Referer'] = $url;
                $guzzleOptions['headers']['X-IG-App-ID'] = '936619743392459';
                $guzzleOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
                $guzzleOptions['headers']['Origin'] = 'https://www.instagram.com';
                $url = sprintf('https://www.instagram.com/api/v1/users/web_profile_info/?username=%s', $account);
                $response = $client->get($url, $guzzleOptions);
            }
        } catch (Exception $e) {
            Craft::error('Error fetching page: ' . $e->getMessage(), 'instagramfeed');

            return null;
        }

        return (string)$response->getBody();
    }

    /**
     * Fetches a tag from Instagram.
     *
     * @param string $tag The tag to fetch.
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function fetchInstagramTag(string $tag): ?string
    {
        $settings = InstagramFeed::getInstance()->getSettings();
        $tag = substr($tag, 1);

        $cookies = new CookieJar();
        $client = new Client([
            'cookies' => $cookies,
        ]);

        $guzzleOptions = [
            'timeout' => $settings->timeout,
            'headers' => [
                'Accept-Language' => 'en-US;q=0.9,en;q=0.8',
                'User-Agent' => self::DEFAULT_USER_AGENT,
            ],
        ];

        try {
            if ($this->canUseProxy()) {
                $url = sprintf('https://igproxy.codemonauts.com/explore/tags/%s/', $tag);
                $referer = Craft::$app->getSites()->getCurrentSite()->getBaseUrl();
                $guzzleOptions['headers']['Authorization'] = $settings->proxyKey;
                $guzzleOptions['headers']['Referer'] = $referer;
                $guzzleOptions['headers']['X-Plugin-Version'] = InstagramFeed::getInstance()->getVersion();
                $response = $client->get($url, $guzzleOptions);
            } else {
                $url = sprintf('https://www.instagram.com/explore/tags/%s/', $tag);
                $client->get($url, $guzzleOptions);
                $guzzleOptions['headers']['Referer'] = $url;
                $guzzleOptions['headers']['X-IG-App-ID'] = '936619743392459';
                $guzzleOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
                $guzzleOptions['headers']['Origin'] = 'https://www.instagram.com';
                $url = sprintf('https://www.instagram.com/api/v1/tags/logged_out_web_info/?tag_name=%s', $tag);
                $response = $client->get($url, $guzzleOptions);
            }
        } catch (Exception $e) {
            Craft::error('Error fetching page: ' . $e->getMessage(), 'instagramfeed');

            return null;
        }

        return (string)$response->getBody();
    }

    /**
     * Function to parse the response body from the proxy.
     *
     * @param string $response
     *
     * @return mixed
     */
    private function parseResponse(string $response)
    {
        /**
         * @var \codemonauts\instagramfeed\models\Settings $settings
         */
        $settings = InstagramFeed::getInstance()->getSettings();

        if ($settings->dump) {
            $this->dumpResponse($response);
        }

        $data = json_decode($response, true);
        if (false === $data || null === $data) {
            return [];
        }

        return $data;
    }

    /**
     * Extracts the posts from the Instagram response
     *
     * @param array $response The response from Instagram
     *
     * @return array
     */
    private function extractMedia(array $response): array
    {
        $items = [];

        $structures = include(__DIR__ . '/../structures.php');

        foreach ($structures as $config) {
            if ($this->isStructure($response, $config['structure'])) {
                $parser = new $config['parser'];
                return $parser->getItems($response);
            }
        }

        // No known structure found, if $response is not empty, we will dump it
        if (!empty($response)) {
            $this->dumpResponse(serialize($response));
        }

        return $items;
    }

    /**
     * Checks if an array matches to a specific structure
     *
     * @param array $haystack The haystack to check for.
     * @param string|array $structure The structure to check against.
     *
     * @return bool
     */
    private function isStructure(array $haystack, $structure): bool
    {
        if (is_string($structure)) {
            $structure = explode('.', $structure);
        }

        if (empty($structure)) {
            return true;
        }
        $node = array_shift($structure);
        if (!isset($haystack[$node])) {
            return false;
        }

        return $this->isStructure($haystack[$node], $structure);
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

        /**
         * @var \codemonauts\instagramfeed\models\Settings $settings
         */
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

    /**
     * Write the response to a file in the storage folder.
     *
     * @param $response
     *
     * @return void
     */
    private function dumpResponse($response): void
    {
        try {
            $timestamp = time();
            $path = Craft::$app->path->getStoragePath() . '/runtime/instagramfeed';
            FileHelper::writeToFile($path . '/' . $timestamp, $response);
            Craft::info('Wrote Instagram response to ' . $path . '/' . $timestamp);
        } catch (Exception $e) {
            Craft::error('Cannot write Instagram response to ' . $path . '/' . $timestamp . ': ' . $e->getMessage());
        }
    }

    public function canUseProxy(): bool
    {
        /**
         * @var \codemonauts\instagramfeed\models\Settings $settings
         */
        $settings = InstagramFeed::getInstance()->getSettings();

        return ($settings->useProxy && $settings->proxyKey !== '');
    }
}
