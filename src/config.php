<?php

return [
    // User name or hash tag to fetch from Instagram.
    'instagramUser' => '',

    // Timeout in seconds waiting for the Instagram page to load.
    'timeout' => 5,

    // Use Guzzle instead of php's file stream
    'useGuzzle' => false,

    // Using your own user agent string, remove this array key to use a common user agent of a well known browser
    'userAgent' => '',
];
