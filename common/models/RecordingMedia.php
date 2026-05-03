<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $recording_id
 * @property int $media_asset_id
 * @property string $role
 * @property int $sort_order
 * @property bool $is_primary
 *
 * @property MediaAsset $mediaAsset
 * @property Recording $recording
 */
final class RecordingMedia extends ActiveRecord
{
    public const string ROLE_AUDIO = 'audio';
    public const string ROLE_VIDEO = 'video';

    public static function tableName(): string
    {
        return '{{%recording_media}}';
    }

    public function rules(): array
    {
        return [
            [['recording_id', 'media_asset_id', 'role'], 'required'],
            [['recording_id', 'media_asset_id', 'sort_order'], 'integer'],
            [['is_primary'], 'boolean'],
            [['role'], 'string', 'max' => 32],
            [['role'], 'in', 'range' => array_keys($this->getRoleList())],
            [
                ['recording_id'],
                'exist',
                'skipOnEmpty' => false,
                'targetClass' => Recording::class,
                'targetAttribute' => ['recording_id' => 'id'],
            ],
            [
                ['media_asset_id'],
                'exist',
                'skipOnEmpty' => false,
                'targetClass' => MediaAsset::class,
                'targetAttribute' => ['media_asset_id' => 'id'],
            ],
        ];
    }

    public function getRoleList(): array
    {
        return [
            self::ROLE_AUDIO => 'Аудио',
            self::ROLE_VIDEO => 'Видео',
        ];
    }

    public function getMediaAsset(): ActiveQuery
    {
        return $this->hasOne(MediaAsset::class, ['id' => 'media_asset_id']);
    }

    public function getRecording(): ActiveQuery
    {
        return $this->hasOne(Recording::class, ['id' => 'recording_id']);
    }
}
