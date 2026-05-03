<?php

declare(strict_types=1);

namespace common\services;

use common\components\storage\StorageInterface;
use common\models\MediaAsset;
use common\models\Recording;
use common\models\RecordingMedia;
use RuntimeException;
use yii\web\UploadedFile;

final class RecordingMediaUploader
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function uploadAudioFile(Recording $recording, UploadedFile $file): void
    {
        $this->replaceRecordingMedia($recording, $file, RecordingMedia::ROLE_AUDIO);
    }

    public function uploadCoverFile(Recording $recording, UploadedFile $file): void
    {
        if ($recording->id === null) {
            throw new RuntimeException('Cannot upload cover for unsaved recording.');
        }

        $existingMediaAsset = $recording->coverMediaAsset;
        $path = $this->createStoragePath($recording, $file, 'cover');
        $this->storage->saveFile($path, $file->tempName);

        $mediaAsset = new MediaAsset([
            'storage' => $this->storage->getStorageName(),
            'path' => $path,
            'original_name' => $file->name,
            'kind' => 'image',
            'mime_type' => $file->type !== '' ? $file->type : null,
            'extension' => $file->extension !== '' ? strtolower($file->extension) : null,
            'size_bytes' => $file->size,
            'checksum_sha256' => hash_file('sha256', $file->tempName) ?: null,
        ]);
        $mediaAsset->save(false);

        $recording->cover_media_asset_id = $mediaAsset->id;
        $recording->save(false, ['cover_media_asset_id', 'updated_at']);

        if ($existingMediaAsset instanceof MediaAsset) {
            $this->storage->delete($existingMediaAsset->path);
            $existingMediaAsset->delete();
        }
    }

    public function uploadVideoFile(Recording $recording, UploadedFile $file): void
    {
        $this->replaceRecordingMedia($recording, $file, RecordingMedia::ROLE_VIDEO);
    }

    private function replaceRecordingMedia(Recording $recording, UploadedFile $file, string $role): void
    {
        if ($recording->id === null) {
            throw new RuntimeException('Cannot upload media for unsaved recording.');
        }

        $existingMediaEntries = RecordingMedia::find()
            ->with(['mediaAsset'])
            ->andWhere([
                'recording_id' => $recording->id,
                'role' => $role,
            ])
            ->all();

        $path = $this->createStoragePath($recording, $file, $role);
        $this->storage->saveFile($path, $file->tempName);

        $mediaAsset = new MediaAsset([
            'storage' => $this->storage->getStorageName(),
            'path' => $path,
            'original_name' => $file->name,
            'kind' => $role,
            'mime_type' => $file->type !== '' ? $file->type : null,
            'extension' => $file->extension !== '' ? strtolower($file->extension) : null,
            'size_bytes' => $file->size,
            'checksum_sha256' => hash_file('sha256', $file->tempName) ?: null,
        ]);
        $mediaAsset->save(false);

        $recordingMedia = new RecordingMedia([
            'recording_id' => $recording->id,
            'media_asset_id' => $mediaAsset->id,
            'role' => $role,
            'sort_order' => 10,
            'is_primary' => true,
        ]);
        $recordingMedia->save(false);

        foreach ($existingMediaEntries as $existingMediaEntry) {
            $this->deleteExistingMedia($existingMediaEntry);
        }
    }

    private function createStoragePath(Recording $recording, UploadedFile $file, string $role): string
    {
        $extension = strtolower((string) $file->extension);
        $suffix = bin2hex(random_bytes(6));

        return 'recordings/' . $recording->slug . '/' . $role . '/' . $suffix . ($extension === '' ? '' : '.' . $extension);
    }

    private function deleteExistingMedia(RecordingMedia $recordingMedia): void
    {
        $mediaAsset = $recordingMedia->mediaAsset;
        $recordingMedia->delete();

        if ($mediaAsset instanceof MediaAsset === false) {
            return;
        }

        $this->storage->delete($mediaAsset->path);
        $mediaAsset->delete();
    }
}
