<?php

declare(strict_types=1);

namespace common\services;

use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\ArtistImage;
use common\models\MediaAsset;
use RuntimeException;
use yii\web\UploadedFile;

final class ArtistImageManager
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function deleteImage(ArtistImage $artistImage): void
    {
        $mediaAsset = $artistImage->mediaAsset;
        $artistImage->delete();

        if ($mediaAsset instanceof MediaAsset === false) {
            return;
        }

        $this->storage->delete($mediaAsset->path);
        $mediaAsset->delete();
    }

    public function uploadImageFile(Artist $artist, UploadedFile $file, int $sortOrder): ArtistImage
    {
        if ($artist->id === null) {
            throw new RuntimeException('Cannot upload image for unsaved artist.');
        }

        $path = $this->createStoragePath($artist, $file);
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

        $artistImage = new ArtistImage([
            'artist_id' => $artist->id,
            'media_asset_id' => $mediaAsset->id,
            'sort_order' => $sortOrder,
            'is_primary' => false,
        ]);
        $artistImage->save(false);

        return $artistImage;
    }

    private function createStoragePath(Artist $artist, UploadedFile $file): string
    {
        $artistSlug = trim((string) $artist->slug);

        if ($artistSlug === '') {
            throw new RuntimeException('Cannot resolve artist slug for image upload.');
        }

        $extension = strtolower((string) $file->extension);
        $suffix = bin2hex(random_bytes(6));

        return 'artists/'
            . $artistSlug
            . '/gallery/'
            . $artistSlug
            . '-'
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
