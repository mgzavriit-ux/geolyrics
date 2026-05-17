<?php

declare(strict_types=1);

namespace backend\models;

use yii\base\Model;

final class ArtistGalleryImageForm extends Model
{
    public $mediaAssetId = 0;
    public $sortOrder = 100;
    public $deleteImage = false;
    public $mimeType = '';
    public $originalName = '';
    public $sizeBytes = null;
    public $publicUrl = '';
    public $height = null;
    public $width = null;

    public function rules(): array
    {
        return [
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
            [['mediaAssetId', 'sortOrder', 'sizeBytes', 'width', 'height'], 'integer'],
            [['deleteImage'], 'boolean'],
            [['mimeType', 'originalName', 'publicUrl'], 'string'],
            [['mediaAssetId'], 'required'],
            [['sortOrder'], 'default', 'value' => 100],
            [['deleteImage'], 'default', 'value' => false],
        ];
    }
}
