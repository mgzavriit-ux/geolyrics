<?php

declare(strict_types=1);

$params = require __DIR__ . '/../../common/config/params.php';

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',
    'modules' => [
        'v1' => [
            'class' => \api\modules\v1\Module::class,
        ],
    ],
    'components' => [
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'request' => [
            'cookieValidationKey' => getenv('API_COOKIE_VALIDATION_KEY') ?: 'api-change-me',
            'csrfParam' => '_csrf-api',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'GET' => 'site/index',
                'GET health' => 'site/health',
                'GET v1/health' => 'v1/health/index',
            ],
        ],
        'user' => [
            'identityClass' => \common\models\User::class,
            'enableSession' => false,
            'loginUrl' => null,
        ],
    ],
    'params' => $params,
];
