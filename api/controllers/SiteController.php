<?php

declare(strict_types=1);

namespace api\controllers;

use common\app\WebApplication;
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
        /** @var WebApplication $app */
        $app = \Yii::$app;

        return [
            'name' => 'geolyrics-api',
            'version' => 'v1',
            'status' => 'ok',
            'endpoints' => [
                '/health',
                '/v1/health',
            ],
            'storage' => [
                'driver' => get_class($app->storage),
                'baseUrl' => $app->storage->getBaseUrl(),
            ],
            'queue' => [
                'driver' => get_class($app->queue),
            ],
        ];
    }
}
