<?php

declare(strict_types=1);

namespace common\services;

use common\components\storage\StorageInterface;
use common\models\MediaAsset;
use common\models\Recording;
use RuntimeException;

final class RecordingRemover
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function deleteRecording(Recording $recording): void
    {
        if ($recording->id === null) {
            return;
        }

        $mediaAssets = $this->collectMediaAssets($recording);

        if ($recording->delete() === false) {
            throw new RuntimeException('Не удалось удалить запись.');
        }

        $this->deleteMediaAssets($mediaAssets);
    }

    /**
     * @return MediaAsset[]
     */
    private function collectMediaAssets(Recording $recording): array
    {
        $mediaAssets = [];

        if ($recording->coverMediaAsset instanceof MediaAsset) {
            $mediaAssets[$recording->coverMediaAsset->id] = $recording->coverMediaAsset;
        }

        foreach ($recording->recordingMediaEntries as $recordingMedia) {
            if ($recordingMedia->mediaAsset instanceof MediaAsset === false) {
                continue;
            }

            $mediaAssets[$recordingMedia->mediaAsset->id] = $recordingMedia->mediaAsset;
        }

        return array_values($mediaAssets);
    }

    /**
     * @param MediaAsset[] $mediaAssets
     */
    private function deleteMediaAssets(array $mediaAssets): void
    {
        foreach ($mediaAssets as $mediaAsset) {
            $this->storage->delete($mediaAsset->path);
            $mediaAsset->delete();
        }
    }
}
