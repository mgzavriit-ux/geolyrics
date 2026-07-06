<?php

declare(strict_types=1);

namespace backend\models;

use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\HomeHeroImage;
use common\models\MediaAsset;
use common\services\HomeHeroImageManager;
use RuntimeException;
use yii\base\Model;
use yii\web\UploadedFile;

final class HomeHeroImageForm extends Model
{
    public $artistId = null;
    public $focalPointX = 50;
    public $focalPointY = 50;
    public $sortOrder = 100;
    public $isActive = true;
    public $imageFile = null;
    public string $imageUrl = '';
    public string $imageName = '';

    private HomeHeroImage $homeHeroImage;
    private HomeHeroImageManager $imageManager;

    public function __construct(HomeHeroImage $homeHeroImage, StorageInterface $storage, array $config = [])
    {
        $this->homeHeroImage = $homeHeroImage;
        $this->imageManager = new HomeHeroImageManager($storage);

        parent::__construct($config);

        $this->initializeAttributes($storage);
    }

    public function rules(): array
    {
        return [
            [
                ['focalPointX', 'focalPointY'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return 50;
                    }

                    return $value;
                },
            ],
            [
                ['sortOrder'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return 100;
                    }

                    return $value;
                },
            ],
            [['artistId'], 'required'],
            [['artistId', 'focalPointX', 'focalPointY', 'sortOrder'], 'integer'],
            [['focalPointX', 'focalPointY'], 'integer', 'min' => 0, 'max' => 100],
            [['isActive'], 'boolean'],
            [['focalPointX', 'focalPointY'], 'default', 'value' => 50],
            [['sortOrder'], 'default', 'value' => 100],
            [['isActive'], 'default', 'value' => true],
            [
                ['artistId'],
                'exist',
                'targetClass' => Artist::class,
                'targetAttribute' => ['artistId' => 'id'],
            ],
            [
                ['imageFile'],
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'checkExtensionByMimeType' => true,
                'skipOnEmpty' => true,
            ],
            [['imageFile'], 'validateImageFile'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'artistId' => 'Исполнитель',
            'imageFile' => 'Фото',
            'focalPointX' => 'Focal point X',
            'focalPointY' => 'Focal point Y',
            'sortOrder' => 'Порядок',
            'isActive' => 'Активно',
        ];
    }

    public function getHomeHeroImage(): HomeHeroImage
    {
        return $this->homeHeroImage;
    }

    public function load($data, $formName = null): bool
    {
        $isLoaded = parent::load($data, $formName);
        $this->imageFile = UploadedFile::getInstance($this, 'imageFile');

        return $isLoaded || $this->imageFile instanceof UploadedFile;
    }

    public function save(): void
    {
        $oldMediaAsset = $this->homeHeroImage->mediaAsset;
        $newMediaAsset = $this->createMediaAsset();

        $this->homeHeroImage->artist_id = (int) $this->artistId;
        $this->homeHeroImage->focal_point_x = (int) $this->focalPointX;
        $this->homeHeroImage->focal_point_y = (int) $this->focalPointY;
        $this->homeHeroImage->sort_order = (int) $this->sortOrder;
        $this->homeHeroImage->is_active = (bool) $this->isActive;

        if ($newMediaAsset instanceof MediaAsset) {
            $this->homeHeroImage->media_asset_id = (int) $newMediaAsset->id;
        }

        $this->homeHeroImage->save(false);

        if ($newMediaAsset instanceof MediaAsset && $oldMediaAsset instanceof MediaAsset) {
            $this->imageManager->deleteMediaAsset($oldMediaAsset);
        }
    }

    public function validateImageFile(string $attribute): void
    {
        if ($this->homeHeroImage->isNewRecord === false) {
            return;
        }

        if ($this->$attribute instanceof UploadedFile) {
            return;
        }

        $this->addError($attribute, 'Загрузите фото для hero-блока.');
    }

    private function createMediaAsset(): MediaAsset | null
    {
        if ($this->imageFile instanceof UploadedFile === false) {
            return null;
        }

        return $this->imageManager->uploadImageFile($this->findArtist(), $this->imageFile);
    }

    private function findArtist(): Artist
    {
        $artist = Artist::findOne((int) $this->artistId);

        if ($artist instanceof Artist) {
            return $artist;
        }

        throw new RuntimeException('Cannot find selected artist for hero image.');
    }

    private function initializeAttributes(StorageInterface $storage): void
    {
        if ($this->homeHeroImage->isNewRecord === true) {
            return;
        }

        $this->artistId = (int) $this->homeHeroImage->artist_id;
        $this->focalPointX = (int) $this->homeHeroImage->focal_point_x;
        $this->focalPointY = (int) $this->homeHeroImage->focal_point_y;
        $this->sortOrder = (int) $this->homeHeroImage->sort_order;
        $this->isActive = (bool) $this->homeHeroImage->is_active;

        $mediaAsset = $this->homeHeroImage->mediaAsset;

        if ($mediaAsset instanceof MediaAsset === false) {
            return;
        }

        $this->imageUrl = $storage->getPublicUrl($mediaAsset->path);
        $this->imageName = $mediaAsset->original_name;
    }
}
