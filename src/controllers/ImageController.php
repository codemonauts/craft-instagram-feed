<?php

namespace codemonauts\instagramfeed\controllers;

use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;

class ImageController extends Controller
{
    public array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public function actionImage($shortCode)
    {
        $filename = Craft::$app->getPath()->getStoragePath() . '/instagram/' . $shortCode . '_full.jpg';

        if (!preg_match('/[a-z0-9\-_]+/i', $shortCode) || !file_exists($filename)) {
            throw new NotFoundHttpException();
        }

        Craft::$app->getResponse()->sendFile($filename);
    }

    public function actionThumb($shortCode)
    {
        $filename = Craft::$app->getPath()->getStoragePath() . '/instagram/' . $shortCode . '_thumb.jpg';

        if (!preg_match('/[a-z0-9\-_]+/i', $shortCode) || !file_exists($filename)) {
            throw new NotFoundHttpException();
        }

        Craft::$app->getResponse()->sendFile($filename);
    }
}
