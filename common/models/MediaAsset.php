<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $storage
 * @property string $path
 * @property string $original_name
 * @property string $kind
 * @property string|null $mime_type
 * @property string|null $extension
 * @property int|null $size_bytes
 * @property string|null $checksum_sha256
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration_ms
 * @property int $created_at
 * @property int $updated_at
 */
final class MediaAsset extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%media_asset}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['storage', 'path', 'original_name', 'kind'], 'required'],
            [['size_bytes', 'width', 'height', 'duration_ms'], 'integer'],
            [['storage', 'kind'], 'string', 'max' => 32],
            [['path'], 'string', 'max' => 1024],
            [['original_name'], 'string', 'max' => 255],
            [['mime_type'], 'string', 'max' => 128],
            [['extension'], 'string', 'max' => 16],
            [['checksum_sha256'], 'string', 'max' => 64],
        ];
    }
}
