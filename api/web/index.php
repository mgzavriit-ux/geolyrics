<?php

declare(strict_types=1);

defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG') ?: '1', FILTER_VALIDATE_BOOL));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = [];

foreach (
    [
        __DIR__ . '/../../common/config/main.php',
        __DIR__ . '/../../common/config/main-local.php',
        __DIR__ . '/../config/main.php',
        __DIR__ . '/../config/main-local.php',
    ] as $configFile
) {
    if (is_file($configFile) === false) {
        continue;
    }

    $config = yii\helpers\ArrayHelper::merge($config, require $configFile);
}

(new yii\web\Application($config))->run();
