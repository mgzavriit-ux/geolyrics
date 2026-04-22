<?php

declare(strict_types=1);

namespace common\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

final class GenerateSongTranslationJob extends BaseObject implements JobInterface
{
    public function __construct(
        private int $songId,
        private string $languageCode,
        array $config = []
    ) {
        parent::__construct($config);
    }

    public function execute($queue): void
    {
        Yii::info(
            [
                'message' => 'Translation generation job placeholder was executed.',
                'songId' => $this->songId,
                'languageCode' => $this->languageCode,
                'queue' => $queue instanceof Queue ? $queue::class : get_debug_type($queue),
            ],
            __METHOD__,
        );
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getSongId(): int
    {
        return $this->songId;
    }
}
