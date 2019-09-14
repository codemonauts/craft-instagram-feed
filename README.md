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
composer require codemonauts/codemonauts/craft-instagram-feed
./craft install/plugin instagramfeed
```

Switch to the settings page in the control panel and enter the name of the Instagram account you want to fetch.

## Usage

To fetch the feed, configured in settings, in your template, just iterate like this:

``` twig
{% for item in craft.instagram.getFeed() %}
<a href="https://www.instagram.com/p/{{ item.shortcode }}/" target="_blank">
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

You can make some more configuration settings in a config file placed in your CraftCMS config directory. You can find the most recent version in src/config.php. You have to name the file ``ìnstagramfeed.php``.

``` php

// User name or hash tag to fetch from Instagram.
'instagramUser' => '',

// Timeout in seconds waiting for the Instagram page to load.
'timeout' => 5,

// Use Guzzle instead of php's file stream
'useGuzzle' => false,

// Using your own user agent string, remove this array key to use a common user agent of a well known browser
'userAgent' => '',

```

## Caching

The feed will be cached for 1 month but will be checked and updated evey 6 hours. If the update fails, the cached feed is used and the update stops for 15 minutes before checking again. 

With ❤ by [codemonauts](https://codemonauts.com)
