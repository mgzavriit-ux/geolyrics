<?php

declare(strict_types=1);

namespace common\app;

use common\components\storage\StorageInterface;
use yii\db\Connection;
use yii\queue\Queue;
use yii\redis\Connection as RedisConnection;
use yii\web\Application;

/**
 * @property Connection $db
 * @property Queue $queue
 * @property RedisConnection $redis
 * @property StorageInterface $storage
 */
final class WebApplication extends Application
{
}
