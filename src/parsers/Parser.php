<?php

namespace codemonauts\instagramfeed\parsers;

use yii\base\Exception;

abstract class Parser
{
    /**
     * Returns the items from the given response.
     *
     * @param array $response The response from Instagram.
     *
     * @return array
     */
    abstract public function getItems(array $response): array;

    /**
     * Returns the picture mapping of the structure.
     *
     * @return array
     */
    abstract protected function getPictureMapping(): array;

    /**
     * Returns the best picture in size from the Instagram result array.
     *
     * @param array $pictures The array of pictures to choose the best version from.
     *
     * @return string
     * @throws \yii\base\Exception
     */
    final protected function getBestPicture(array $pictures): string
    {
        $url = '';
        $maxPixels = 0;

        $mapping = $this->getPictureMapping();

        foreach ($pictures as $picture) {
            if (!isset($picture[$mapping['width']])) {
                throw new Exception('Wrong mapping of width.');
            }
            $pixels = $picture[$mapping['width']] * $picture[$mapping['height']];
            if ($pixels > $maxPixels) {
                $url = $picture[$mapping['url']];

                $maxPixels = $pixels;
            }
        }

        return $url;
    }
}