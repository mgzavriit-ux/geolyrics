<?php

declare(strict_types=1);

namespace backend\models;

use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\ArtistImage;
use common\models\MediaAsset;
use common\services\ArtistImageManager;
use RuntimeException;
use yii\base\Model;
use yii\web\UploadedFile;

final class ArtistGalleryForm extends Model
{
    /**
     * @var UploadedFile[]
     */
    public array $newImageFiles = [];

    public $primaryMediaAssetId = null;

    private Artist $artist;
    private ArtistImageManager $imageManager;

    /**
     * @var array<int, ArtistImage>
     */
    private array $artistImagesByMediaAssetId = [];

    /**
     * @var ArtistGalleryImageForm[]
     */
    private array $imageModels = [];

    public function __construct(Artist $artist, StorageInterface $storage, array $config = [])
    {
        $this->artist = $artist;
        $this->imageManager = new ArtistImageManager($storage);

        parent::__construct($config);

        $this->initializeImageModels($storage);
    }

    public function rules(): array
    {
        return [
            [
                ['primaryMediaAssetId'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['primaryMediaAssetId'], 'integer'],
            [
                ['newImageFiles'],
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'checkExtensionByMimeType' => true,
                'maxFiles' => 20,
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function getImageModels(): array
    {
        return $this->imageModels;
    }

    public function load($data, $formName = null): bool
    {
        $isLoaded = parent::load($data, $formName);
        $isLoaded = Model::loadMultiple($this->imageModels, $data) || $isLoaded;
        $this->newImageFiles = UploadedFile::getInstances($this, 'newImageFiles');

        return $isLoaded || $this->newImageFiles !== [];
    }

    public function save(): void
    {
        if ($this->artist->id === null) {
            throw new RuntimeException('Cannot save gallery for unsaved artist.');
        }

        $keptImages = $this->saveExistingImages();
        $uploadedImages = $this->uploadNewImages($keptImages);
        $this->applyPrimaryImage($keptImages, $uploadedImages);
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $isValid = parent::validate($attributeNames, $clearErrors);

        return Model::validateMultiple($this->imageModels) && $isValid;
    }

    private function applyPrimaryImage(array $keptImages, array $uploadedImages): void
    {
        $primaryMediaAssetId = $this->findPrimaryMediaAssetId($keptImages, $uploadedImages);
        $allImages = array_merge($keptImages, $uploadedImages);

        foreach ($allImages as $artistImage) {
            $artistImage->is_primary = $primaryMediaAssetId !== null
                && (int) $artistImage->media_asset_id === $primaryMediaAssetId;
            $artistImage->save(false, ['is_primary']);
        }
    }

    private function findNextSortOrder(array $keptImages): int
    {
        $maxSortOrder = 0;

        foreach ($keptImages as $artistImage) {
            $maxSortOrder = max($maxSortOrder, (int) $artistImage->sort_order);
        }

        return $maxSortOrder + 10;
    }

    private function findPrimaryMediaAssetId(array $keptImages, array $uploadedImages): int | null
    {
        if ($this->primaryMediaAssetId !== null) {
            foreach ($keptImages as $artistImage) {
                if ((int) $artistImage->media_asset_id === $this->primaryMediaAssetId) {
                    return $this->primaryMediaAssetId;
                }
            }
        }

        if ($keptImages !== []) {
            usort($keptImages, static function (ArtistImage $leftImage, ArtistImage $rightImage): int {
                $sortOrderComparison = (int) $leftImage->sort_order <=> (int) $rightImage->sort_order;

                if ($sortOrderComparison !== 0) {
                    return $sortOrderComparison;
                }

                return (int) $leftImage->media_asset_id <=> (int) $rightImage->media_asset_id;
            });

            return (int) $keptImages[0]->media_asset_id;
        }

        if ($uploadedImages !== []) {
            return (int) $uploadedImages[0]->media_asset_id;
        }

        return null;
    }

    private function initializeImageModels(StorageInterface $storage): void
    {
        foreach ($this->artist->artistImages as $artistImage) {
            $mediaAsset = $artistImage->mediaAsset;

            if ($mediaAsset instanceof MediaAsset === false) {
                continue;
            }

            $this->artistImagesByMediaAssetId[(int) $artistImage->media_asset_id] = $artistImage;
            $this->imageModels[] = new ArtistGalleryImageForm([
                'mediaAssetId' => (int) $artistImage->media_asset_id,
                'sortOrder' => (int) $artistImage->sort_order,
                'deleteImage' => false,
                'mimeType' => (string) ($mediaAsset->mime_type ?? ''),
                'originalName' => $mediaAsset->original_name,
                'sizeBytes' => $mediaAsset->size_bytes === null ? null : (int) $mediaAsset->size_bytes,
                'publicUrl' => $storage->getPublicUrl($mediaAsset->path),
                'height' => $mediaAsset->height === null ? null : (int) $mediaAsset->height,
                'width' => $mediaAsset->width === null ? null : (int) $mediaAsset->width,
            ]);

            if ((bool) $artistImage->is_primary === true) {
                $this->primaryMediaAssetId = (int) $artistImage->media_asset_id;
            }
        }
    }

    /**
     * @param ArtistImage[] $keptImages
     *
     * @return ArtistImage[]
     */
    private function uploadNewImages(array $keptImages): array
    {
        $uploadedImages = [];
        $sortOrder = $this->findNextSortOrder($keptImages);

        foreach ($this->newImageFiles as $index => $newImageFile) {
            $uploadedImages[] = $this->imageManager->uploadImageFile(
                $this->artist,
                $newImageFile,
                $sortOrder + ($index * 10),
            );
        }

        return $uploadedImages;
    }

    /**
     * @return ArtistImage[]
     */
    private function saveExistingImages(): array
    {
        $keptImages = [];

        foreach ($this->imageModels as $imageModel) {
            $artistImage = $this->artistImagesByMediaAssetId[(int) $imageModel->mediaAssetId] ?? null;

            if ($artistImage instanceof ArtistImage === false) {
                continue;
            }

            if ((bool) $imageModel->deleteImage === true) {
                $this->imageManager->deleteImage($artistImage);
                continue;
            }

            $artistImage->sort_order = (int) $imageModel->sortOrder;
            $artistImage->save(false, ['sort_order']);
            $keptImages[] = $artistImage;
        }

        return $keptImages;
    }
}
