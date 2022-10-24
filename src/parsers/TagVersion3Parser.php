<?php

namespace codemonauts\instagramfeed\parsers;

class TagVersion3Parser extends Parser
{
    /**
     * @inheritDoc
     */
    public function getItems(array $response): array
    {
        $items = [];

        if (!isset($response['data']['hashtag']['edge_hashtag_to_media']['edges'])) {
            return $items;
        }

        $medias = array_slice($response['data']['hashtag']['edge_hashtag_to_media']['edges'], 0,24);

        foreach ($medias as $media) {
            $item['thumbnailSource'] = $this->getBestPicture($media['node']['thumbnail_resources']);
            $item['imageSource'] = $media['node']['display_url'];
            $item['likes'] = $media['node']['edge_liked_by']['count'] ?? 0;
            $item['comments'] = $media['node']['edge_media_to_comment']['count'] ?? 0;
            $item['shortcode'] = $media['node']['shortcode'];
            $item['timestamp'] = $media['node']['taken_at_timestamp'];
            $item['caption'] = $media['node']['edge_media_to_caption']['edges'][0]['node']['text'] ?? '';
            $item['isVideo'] = (bool)$media['node']['is_video'];
            if ($item['isVideo']) {
                $item['hasAudio'] = isset($media['node']['has_audio']) && $media['node']['has_audio'];
                $item['video_view_count'] = $media['node']['video_view_count'] ?? 0;
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    protected function getPictureMapping(): array
    {
        return [
            'width' => 'config_width',
            'height' => 'config_height',
            'url' => 'src',
        ];
    }
}