<?php

declare(strict_types=1);

namespace backend\models;

use yii\base\Model;
use yii\web\UploadedFile;

final class SongCoverUploadForm extends Model
{
    public UploadedFile | null $coverFile = null;

    public function rules(): array
    {
        return [
            [
                ['coverFile'],
                'file',
                'skipOnEmpty' => true,
                'checkExtensionByMimeType' => false,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'maxSize' => 1024 * 1024 * 20,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'coverFile' => 'Обложка песни',
        ];
    }

    public function hasContent(): bool
    {
        return $this->coverFile instanceof UploadedFile;
    }
}
