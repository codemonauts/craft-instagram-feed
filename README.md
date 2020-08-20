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

## Usage

To fetch the feed, configured in settings, in your template, just iterate like this:

``` twig
{% for item in craft.instagram.getFeed() %}
<a href="https://www.instagram.com/p/{{ item.shortcode }}/" target="_blank" rel="noopener noreferrer">
  <img src="{{ item.src }}" alt="" />
</a>
<p>{{ item.caption }}</p>
<p>{{ item.likes }} Likes / {{ item.comments }} Comments</p>
{% endfor %}
```

In PHP do:

``` php
$feed = InstagramFeed::getInstance()->instagramService->getFeed();
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

## Settings

You can make some more configuration settings in a config file placed in your CraftCMS config directory. You can find the most recent version in src/config.php. You have to name the file `instagramfeed.php`.

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

```

## Caching

The feed will be cached for 1 month but will be checked and updated evey 6 hours. If the update fails, the cached feed is used and the update stops for 15 minutes before checking again. 

## It's not working...

Feel free to open an issue on GitHub. We will help you as soon as possible.

If you run your site in devMode, the plugin logs some informations we need to assist you. So please have this logs on hand if you open an issue.

You can enable the `dump` switch in your config file (see above). It will write the response of the request into separate files (one per request) in `CRAFT_STORAGE_PATH/runtime/instagramfeed`. Here you can check if you get a valid response from Instagram.

### Blocked requests

As you know, Instagram is a Walled Garden and they are not very happy to see their data on other sites. And they are heavily working on blocking requests not coming from their platforms. So something can break in this plugin at anytime, when trying to fetch the feed.

If you see an error like

```
0000-00-00 00:00:00 [][][][error][codemonauts\instagramfeed\services\InstagramService::parseInstagramResponse] Instagram responsed with an error: Sorry, there was a problem with your request. 
```

your IP addresses could be blacklisted by Instagram. Please check first, if this happens on other IPs / IP ranges as well.

With ❤ by [codemonauts](https://codemonauts.com)
