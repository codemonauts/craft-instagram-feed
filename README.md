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
```

In the control panel, go to Settings → Plugins and click the “install” button for *Instagram feed*. Then you will be redirected to the settings page, where you can enter the username of the Instagram account you want to fetch.

## Usage

To fetch the feed in your template, just iterate like this:

``` twig
{% for item in craft.instagram.getFeed() %}
<a href="https://www.instagram.com/p/{{ item.shortcode }}/" target="_blank">
  <img src="{{ item.src }}" alt="" />
</a>
<p>{{ item.likes }} Likes / {{ item.comments }} Comments</p>
{% endfor %}
```

In PHP do:

``` php
$feed = InstagramFeed::getInstance()->instagramService->getFeed();
```

## Caching

The feed will be cached for 1 month but will be checked and updated evey 6 hours. If the update fails, the cached feed is used and the update stops for 15 minutes before checking again. 

With ❤ by [codemonauts](https://codemonauts.com)
