<?php

return [
    // User name or hash tag to fetch from Instagram.
    'instagramUser' => '',

    // Use codemonauts proxy to get the Instagram page
    'useProxy' => false,

    // The proxy key to use for authentication
    'proxyKey' => '',

    // Timeout in seconds waiting for the Instagram page to load.
    'timeout' => 10,

    // Dump Instagram response to file for debugging purpose. In an event of unknown structure, it will happen automatically.
    // You will find the dumps in Craft's storage path in the folder runtime/instagramfeed with the timestamp as file name.
    'dump' => false,

    // Use volume to store Instagram images locally, otherwise the storage path will be used
    'useVolume' => false,

    // The handle of the volume to use for storing Instagram images locally
    'volume' => '',

    // Subpath to use in volume
    'subpath' => '',
];
