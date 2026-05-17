<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $artist_id
 * @property int $media_asset_id
 * @property int $sort_order
 * @property bool $is_primary
 *
 * @property Artist $artist
 * @property MediaAsset $mediaAsset
 */
final class ArtistImage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%artist_image}}';
    }

    public function rules(): array
    {
        return [
            [['artist_id', 'media_asset_id', 'sort_order'], 'integer'],
            [['is_primary'], 'boolean'],
            [['artist_id', 'media_asset_id'], 'required'],
            [['sort_order'], 'default', 'value' => 100],
            [['is_primary'], 'default', 'value' => false],
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
            'artist_id' => 'Исполнитель',
            'media_asset_id' => 'Изображение',
            'sort_order' => 'Порядок',
            'is_primary' => 'Основное',
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
