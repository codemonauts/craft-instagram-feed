<?php

return [
    [
        'structure' => 'data.user.edge_owner_to_timeline_media.edges',
        'parser' => codemonauts\instagramfeed\parsers\AccountVersion1Parser::class,
    ],
    [
        'structure' => 'items.0',
        'parser' => codemonauts\instagramfeed\parsers\AccountVersion2Parser::class,
    ],
    [
        'structure' => 'entry_data.TagPage.0.graphql.hashtag.edge_hashtag_to_media.edges',
        'parser' => codemonauts\instagramfeed\parsers\TagVersion1Parser::class,
    ],
    [
        'structure' => 'data.top.sections',
        'parser' => codemonauts\instagramfeed\parsers\TagVersion2Parser::class,
    ],
    [
        'structure' => 'data.recent.sections',
        'parser' => codemonauts\instagramfeed\parsers\TagVersion2Parser::class,
    ],
    [
        'structure' => 'data.hashtag.edge_hashtag_to_media.edges',
        'parser' => codemonauts\instagramfeed\parsers\TagVersion3Parser::class,
    ],
];