<?php

declare(strict_types=1);

namespace backend\models;

use yii\base\Model;
use yii\web\UploadedFile;

final class RecordingMediaUploadForm extends Model
{
    public UploadedFile | null $audioFile = null;
    public UploadedFile | null $coverFile = null;
    public UploadedFile | null $videoFile = null;

    public function rules(): array
    {
        return [
            [
                ['audioFile'],
                'file',
                'skipOnEmpty' => true,
                'checkExtensionByMimeType' => false,
                'extensions' => ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'],
                'maxSize' => 1024 * 1024 * 500,
            ],
            [
                ['coverFile'],
                'file',
                'skipOnEmpty' => true,
                'checkExtensionByMimeType' => false,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'maxSize' => 1024 * 1024 * 20,
            ],
            [
                ['videoFile'],
                'file',
                'skipOnEmpty' => true,
                'checkExtensionByMimeType' => false,
                'extensions' => ['mp4', 'mov', 'm4v', 'webm', 'avi', 'mkv'],
                'maxSize' => 1024 * 1024 * 1024,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'audioFile' => 'Аудиофайл',
            'coverFile' => 'Обложка',
            'videoFile' => 'Видеофайл',
        ];
    }

    public function hasContent(): bool
    {
        return $this->audioFile instanceof UploadedFile
            || $this->videoFile instanceof UploadedFile
            || $this->coverFile instanceof UploadedFile;
    }
}
