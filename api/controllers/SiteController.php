<?php

declare(strict_types=1);

namespace api\controllers;

use yii\web\Controller;
use yii\web\ErrorAction;

final class SiteController extends Controller
{
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionHealth(): array
    {
        return [
            'name' => 'geolyrics-api',
            'status' => 'ok',
            'time' => gmdate(DATE_ATOM),
        ];
    }

    public function actionIndex(): array
    {
        return [
            'name' => 'geolyrics-api',
            'version' => 'v1',
            'status' => 'ok',
            'endpoints' => [
                '/health',
                '/v1/health',
            ],
            'storage' => [
                'driver' => get_class(\Yii::$app->storage),
                'baseUrl' => \Yii::$app->storage->baseUrl,
            ],
            'queue' => [
                'driver' => get_class(\Yii::$app->queue),
            ],
        ];
    }
}
