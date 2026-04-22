<?php

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: 'postgres';
$dbPort = getenv('DB_PORT') ?: '5432';
$dbName = getenv('DB_NAME') ?: 'geolyrics';
$dbUser = getenv('DB_USER') ?: 'geolyrics';
$dbPassword = getenv('DB_PASSWORD') ?: 'geolyrics';
$redisHost = getenv('REDIS_HOST') ?: 'redis';
$redisPort = (int) (getenv('REDIS_PORT') ?: 6379);
$redisDatabase = (int) (getenv('REDIS_DATABASE') ?: 0);
$queueChannel = getenv('QUEUE_CHANNEL') ?: 'geolyrics.queue';
$storageBasePath = getenv('STORAGE_BASE_PATH') ?: Yii::getAlias('@storage/uploads');
$storageBaseUrl = getenv('STORAGE_BASE_URL') ?: '/uploads';

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@uploads' => $storageBasePath,
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\redis\Cache::class,
            'redis' => 'redis',
            'keyPrefix' => 'geolyrics:cache:',
        ],
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}",
            'username' => $dbUser,
            'password' => $dbPassword,
            'charset' => 'utf8',
            'schemaMap' => [
                'pgsql' => [
                    'class' => \yii\db\pgsql\Schema::class,
                    'defaultSchema' => 'public',
                ],
            ],
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis',
            'channel' => $queueChannel,
            'as log' => \yii\queue\LogBehavior::class,
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'hostname' => $redisHost,
            'port' => $redisPort,
            'database' => $redisDatabase,
        ],
        'storage' => [
            'class' => \common\components\storage\LocalStorage::class,
            'basePath' => $storageBasePath,
            'baseUrl' => $storageBaseUrl,
        ],
    ],
];
