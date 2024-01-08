<?php

namespace codemonauts\instagramfeed\parsers;

class AccountVersion2Parser extends Parser
{
    /**
     * @inheritDoc
     */
    public function getItems(array $response): array
    {
        $items = [];

        if (!isset($response['items'][0])) {
            return $items;
        }

        foreach ($response['items'] as $media) {
            $item['thumbnailSource'] = $this->getBestPicture($media['image_versions2']['candidates']);
            $item['imageSource'] = $item['thumbnailSource'];
            $item['likes'] = $media['like_count'] ?? 0;
            $item['comments'] = $media['comment_count'] ?? 0;
            $item['shortcode'] = $media['code'];
            $item['timestamp'] = $media['taken_at'];
            $item['caption'] = $media['caption']['text'] ?? '';
            $item['isVideo'] = $media['media_type'] === 2;
            if ($item['isVideo']) {
                $item['hasAudio'] = isset($media['has_audio']) && $media['has_audio'];
                $item['video_view_count'] = $media['play_count'] ?? 0;
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
            'width' => 'width',
            'height' => 'height',
            'url' => 'url',
        ];
    }
}