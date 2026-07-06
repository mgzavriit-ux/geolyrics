<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $artist_id
 * @property int $media_asset_id
 * @property int $focal_point_x
 * @property int $focal_point_y
 * @property int $sort_order
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Artist $artist
 * @property MediaAsset $mediaAsset
 */
final class HomeHeroImage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%home_hero_image}}';
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
            [['artist_id', 'media_asset_id'], 'required'],
            [['artist_id', 'media_asset_id', 'focal_point_x', 'focal_point_y', 'sort_order'], 'integer'],
            [['focal_point_x', 'focal_point_y'], 'integer', 'min' => 0, 'max' => 100],
            [['is_active'], 'boolean'],
            [['focal_point_x', 'focal_point_y'], 'default', 'value' => 50],
            [['sort_order'], 'default', 'value' => 100],
            [['is_active'], 'default', 'value' => true],
            [
                ['artist_id'],
                'exist',
                'targetClass' => Artist::class,
                'targetAttribute' => ['artist_id' => 'id'],
            ],
            [
                ['media_asset_id'],
                'exist',
                'targetClass' => MediaAsset::class,
                'targetAttribute' => ['media_asset_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'artist_id' => 'Исполнитель',
            'media_asset_id' => 'Фото',
            'focal_point_x' => 'Focal point X',
            'focal_point_y' => 'Focal point Y',
            'sort_order' => 'Порядок',
            'is_active' => 'Активно',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    public function getArtist(): ActiveQuery
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getMediaAsset(): ActiveQuery
    {
        return $this->hasOne(MediaAsset::class, ['id' => 'media_asset_id']);
    }
}
