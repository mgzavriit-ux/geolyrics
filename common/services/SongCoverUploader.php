<?php

declare(strict_types=1);

namespace common\services;

use common\components\storage\StorageInterface;
use common\models\MediaAsset;
use common\models\Song;
use RuntimeException;
use yii\web\UploadedFile;

final class SongCoverUploader
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function uploadCoverFile(Song $song, UploadedFile $file): void
    {
        if ($song->id === null) {
            throw new RuntimeException('Cannot upload cover for unsaved song.');
        }

        $existingMediaAsset = $song->coverMediaAsset;
        $path = $this->createStoragePath($song, $file);
        $this->storage->saveFile($path, $file->tempName);

        $dimensions = $this->findImageDimensions($file);
        $mediaAsset = new MediaAsset([
            'storage' => $this->storage->getStorageName(),
            'path' => $path,
            'original_name' => $file->name,
            'kind' => 'image',
            'mime_type' => $file->type !== '' ? $file->type : null,
            'extension' => $file->extension !== '' ? strtolower($file->extension) : null,
            'size_bytes' => $file->size,
            'checksum_sha256' => hash_file('sha256', $file->tempName) ?: null,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);
        $mediaAsset->save(false);

        $song->cover_media_asset_id = $mediaAsset->id;
        $song->save(false, ['cover_media_asset_id', 'updated_at']);

        if ($existingMediaAsset instanceof MediaAsset) {
            $this->storage->delete($existingMediaAsset->path);
            $existingMediaAsset->delete();
        }
    }

    private function createStoragePath(Song $song, UploadedFile $file): string
    {
        $songSlug = trim((string) $song->slug);

        if ($songSlug === '') {
            throw new RuntimeException('Cannot resolve song slug for cover upload.');
        }

        $extension = strtolower((string) $file->extension);
        $suffix = bin2hex(random_bytes(6));

        return 'songs/covers/'
            . $songSlug
            . '/'
            . $songSlug
            . '-cover-'
            . $suffix
            . ($extension === '' ? '' : '.' . $extension);
    }

    /**
     * @return array{width:int|null, height:int|null}
     */
    private function findImageDimensions(UploadedFile $file): array
    {
        $imageInfo = @getimagesize($file->tempName);

        if (is_array($imageInfo) === false) {
            return [
                'width' => null,
                'height' => null,
            ];
        }

        return [
            'width' => isset($imageInfo[0]) ? (int) $imageInfo[0] : null,
            'height' => isset($imageInfo[1]) ? (int) $imageInfo[1] : null,
        ];
    }
}
