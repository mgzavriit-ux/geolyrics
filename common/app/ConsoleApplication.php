<?php

declare(strict_types=1);

namespace common\app;

use common\components\storage\StorageInterface;
use yii\console\Application;
use yii\db\Connection;
use yii\queue\Queue;
use yii\redis\Connection as RedisConnection;

/**
 * @property Connection $db
 * @property Queue $queue
 * @property RedisConnection $redis
 * @property StorageInterface $storage
 */
final class ConsoleApplication extends Application
{
}
