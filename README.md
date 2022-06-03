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

## Documentation

You find the plugin documentation [here](https://plugins.codemonauts.com/plugins/instagramfeed/Introduction.html).

## Blocked requests

As you know, Instagram is a Walled Garden and they are not very happy to see *their* data on other sites. And they are heavily working on blocking requests not coming from their platforms. So something can break in this plugin at anytime, when trying to fetch the feed.

Known actions from Instagram:

 * 2018, disabling the old, public API.
 * March 2020, disabling the token authentication for their new API.
 * April 2020, blocking IP addresses from IP ranges not used for client access.
 * April 2021, using "cross-origin-resource-policy" with "same-site" to block browsers from loading images inside another website which is not "instagram.com".
 * June 2022, relaunch of the Instagram website in React. The JSON is now fetched in a second request.

Please be aware, that you use this plugin at your own risk. Please use this plugin only in a fair manner, for example to show the images of your own Instagram account on your website and link them back to the original post on Instagram. This symbiosis should be fine for Instagram.

With ❤ by [codemonauts](https://codemonauts.com)
