<?php

namespace codemonauts\instagramfeed\parsers;

class TagVersion2Parser extends Parser
{
    /**
     * @inheritDoc
     */
    public function getItems(array $response): array
    {
        $items = [];

        if (!isset($response['data']['top']['sections']) && !isset($response['data']['recent']['sections'])) {
            return $items;
        }

        $top = array_slice($response['data']['top']['sections'], 0, 8);
        $recent = array_slice($response['data']['recent']['sections'], 0, 8);

        $sections = array_merge($top, $recent);

        foreach ($sections as $section) {
            if ($section['layout_type'] !== 'media_grid') {
                continue;
            }
            foreach ($section['layout_content']['medias'] as $node) {
                if ((int)$node['media']['media_type'] === 8) {
                    if (!isset($node['media']['carousel_media'][0]['image_versions2'])) {
                        continue;
                    }
                    $item['thumbnailSource'] = $this->getBestPicture($node['media']['carousel_media'][0]['image_versions2']['candidates']);
                } else {
                    $item['thumbnailSource'] = $this->getBestPicture($node['media']['image_versions2']['candidates']);
                }
                $item['imageSource'] = $item['thumbnailSource'];
                $item['likes'] = $node['media']['like_count'] ?? 0;
                $item['comments'] = $node['media']['comment_count'] ?? 0;
                $item['shortcode'] = $node['media']['code'];
                $item['timestamp'] = $node['media']['taken_at'];
                $item['caption'] = $node['media']['caption']['text'] ?? '';
                $item['isVideo'] = (int)$node['media']['media_type'] === 2;
                if ($item['isVideo']) {
                    $item['hasAudio'] = isset($node['media']['has_audio']) && $node['media']['has_audio'];
                    $item['video_view_count'] = $node['media']['video_view_count'] ?? 0;
                }
                $items[] = $item;
            }
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
