<?php

namespace codemonauts\instagramfeed\parsers;

abstract class Parser
{
    abstract public function getItems(array $response): array;

    abstract protected function getPictureMapping(): array;

    /**
     * Returns the best picture in size from the Instagram result array.
     *
     * @param array $pictures The array of pictures to choose the best version from.
     * @param array $mapping
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
                throw new \yii\base\Exception('hier');
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