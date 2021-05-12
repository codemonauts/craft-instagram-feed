# Instagram feed plugin for Craft CMS 3.x

![Icon](resources/instagram.png)

A plugin for Craft CMS that helps you get your Instagram feed data.

## Background

If you want to add your (or someone else) Instagram feed on your site, you can use this plugin to fetch and cache the feed. It returns the image source, the number of likes and comments and the shortcode of the posts.

This only works with **public** profiles. 

## Requirements

 * Craft CMS >= 3.0.0

## Installation

Open your terminal and go to your Craft project:

``` shell
cd /path/to/project
composer require codemonauts/craft-instagram-feed
./craft install/plugin instagramfeed
```

Switch to the settings page in the control panel and enter the name of the Instagram account you want to fetch.

You can also configure a volume where the images are stored locally.

## Usage

To fetch the feed, configured in settings, in your template, just iterate like this:

``` twig
{% for item in craft.instagram.getFeed() %}
<a href="https://www.instagram.com/p/{{ item.shortcode }}/" target="_blank" rel="noopener noreferrer">
  <img src="{{ item.thumbnail }}" alt="" />
</a>
<p>{{ item.caption }}</p>
<p>{{ item.likes }} Likes / {{ item.comments }} Comments</p>
{% endfor %}
```

If you are using a volume to store the images locally you have a full asset object and you can do things like this:

``` twig
{% for item in craft.instagram.getFeed() %}
<a href="https://www.instagram.com/p/{{ item.shortcode }}/" target="_blank" rel="noopener noreferrer">
  <img src="{{ item.asset.getUrl({ mode: fit, width: 100, height: 100, format: 'webp'}) }}" alt="" />
</a>
<p>{{ item.caption }}</p>
<p>{{ item.likes }} Likes / {{ item.comments }} Comments</p>
{% endfor %}
```

In PHP do:

``` php
$feed = InstagramFeed::getInstance()->instagramService->getFeed();
```
The function returns an array of posts with the following keys:

``` php
[
  'src' => '',                # Same as thumbnail
  'thumbnail' = '',           # URL to the locally stored thumbnail
  'thumbnailSource' = '',     # URL to the thumbnail provided by Instagram
  'image' => '',              # URL to the locally stored original image
  'imageSource' => '',        # URL to the original image provided by Instagram
  'asset' => null,            # The asset object of the locally stored original image (only available when using volumes)
  'assetId' => 0,             # Asset ID of the locally stored original image (only available when using volumes)
  'likes' => 0,               # Number of likes
  'comments' => 0,            # Number of comments
  'shortcode' => '',          # The IG shortcode for the post
  'timestamp' => 0,           # Unix timestamp when the picture/ video was taken
  'caption' => '',            # The caption of the post
  'isVideo' => false,         # If the post is a video
  'hasAudio' => false         # If the video has audio
  'video_view_count' => 0,    # Number of video views
]
```

You can also get the current configured Instagram account (or hash tag) in your templates:

``` twig
{{ craft.instagram.getAccount() }}
```

You can also pass an account name or hash tag to overwrite the account from the settings:

``` twig
{# fetch account #}
{% set items = craft.instagram.getFeed("codemonauts") %}

{# fetch hash tag #}
{% set items = craft.instagram.getFeed("#mrmcd2019") %}
```

``` php
// fetch account
$feed = InstagramFeed::getInstance()->instagramService->getFeed('codemonauts');

// fetch hash tag
$feed = InstagramFeed::getInstance()->instagramService->getFeed('#mrmcd2019');
```

## Local storage

Since the end of April 2021, Instagram sets the "cross-origin-resource-policy" header to "same-origin" to all their images, which means that your browser is not allowed to load the images inside another website which is not "instagram.com".

The only way to make this plugin still usable is to download and store the images locally and deliver them by your webserver.

So since version 1.1.0 we download the images and store them either in Craft's storage path in a folder called "instagram" or you can configure a volume and a subpath.

We recommend using a volume to store the images. **If you are hosting a multi-server environment, you have to use a volume to share the images between all servers.**

One benefit of the volume is, that you will get a full asset object for every image. So you can also use transformations etc.

## Settings

You can make some more configuration settings in a config file placed in your Craft's config directory. You can find the most recent version in src/config.php. You have to name the file `instagramfeed.php`.

``` php

// User name or hash tag to fetch from Instagram.
'instagramUser' => '',

// Timeout in seconds waiting for the Instagram page to load.
'timeout' => 5,

// Use Guzzle instead of php's file stream
'useGuzzle' => false,

// Dump Instagram response to file for debugging purpose.
'dump' => false,

// Using your own user agent string, remove this array key to use a common user agent of a well known browser
'userAgent' => '',

// Use volume to store Instagram images locally, otherwise the storage path will be used
'useVolume' => false,

// The handle of the volume to use for storing Instagram images locally
'volume' => '',

// Subpath to use in volume
'subpath' => '',
```

## Caching

The feed will be cached for 1 month but will be checked and updated evey 6 hours. If the update fails, the cached feed is used and the update stops for 15 minutes before checking again. 

## It's not working...

Feel free to open an issue on GitHub. We will help you as soon as possible.

If you run your site in devMode, the plugin logs some informations we need to assist you. So please have this logs on hand if you open an issue.

You can enable the `dump` switch in your config file (see above). It will write the response of the request into separate files (one per request) in `CRAFT_STORAGE_PATH/runtime/instagramfeed`. Here you can check if you get a valid response from Instagram.

If you see an error like

```
0000-00-00 00:00:00 [][][][error][codemonauts\instagramfeed\services\InstagramService::parseInstagramResponse] Instagram tag data could not be fetched. 
```

your IP addresses could be blacklisted by Instagram. Please check first, if this happens on other IPs / IP ranges as well.

## Blocked requests

As you know, Instagram is a Walled Garden and they are not very happy to see *their* data on other sites. And they are heavily working on blocking requests not coming from their platforms. So something can break in this plugin at anytime, when trying to fetch the feed.

Known actions from Instagram:

 * 2018, disabling the old, public API.
 * March 2020, disabling the token authentication for their new API.
 * April 2020, blocking IP addresses from IP ranges not used for client access.
 * April 2021, using "cross-origin-resource-policy" with "same-site" to block browsers from loading images inside another website which is not "instagram.com".

Please be aware, that you use this plugin at your own risk. Please use this plugin only in a fair manner, for example to show the images of your own Instagram account on your website and link them back to the original post on Instagram. This symbiosis should be fine for Instagram.

With ❤ by [codemonauts](https://codemonauts.com)
