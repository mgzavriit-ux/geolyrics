<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property string $slug
 * @property string $default_title
 * @property string $recording_type
 * @property string $publication_status
 * @property int|null $cover_media_asset_id
 * @property int|null $release_year
 * @property int|null $duration_ms
 * @property string|null $chords_text
 * @property string|null $description
 * @property int|null $published_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Song $song
 * @property MediaAsset|null $coverMediaAsset
 */
final class Recording extends ActiveRecord
{
    public const PUBLICATION_STATUS_DRAFT = 'draft';
    public const PUBLICATION_STATUS_PUBLISHED = 'published';

    public const TYPE_LIVE = 'live';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_STUDIO = 'studio';
    public const TYPE_VIDEO = 'video';

    public static function tableName(): string
    {
        return '{{%recording}}';
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
            [['song_id', 'slug', 'default_title', 'recording_type'], 'required'],
            [
                ['cover_media_asset_id', 'release_year', 'duration_ms', 'published_at'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['song_id', 'cover_media_asset_id', 'release_year', 'duration_ms', 'published_at'], 'integer'],
            [['chords_text', 'description'], 'string'],
            [['slug'], 'string', 'max' => 128],
            [['default_title'], 'string', 'max' => 255],
            [['recording_type', 'publication_status'], 'string', 'max' => 32],
            [['publication_status'], 'default', 'value' => self::PUBLICATION_STATUS_DRAFT],
            [['recording_type'], 'in', 'range' => array_keys($this->getRecordingTypeList())],
            [['publication_status'], 'in', 'range' => array_keys($this->getPublicationStatusList())],
            [['slug'], 'unique'],
            [['song_id'], 'exist', 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
            [['cover_media_asset_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => MediaAsset::class, 'targetAttribute' => ['cover_media_asset_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'song_id' => 'Песня',
            'slug' => 'Slug',
            'default_title' => 'Основное название',
            'recording_type' => 'Тип записи',
            'publication_status' => 'Статус',
            'cover_media_asset_id' => 'Обложка',
            'release_year' => 'Год релиза',
            'duration_ms' => 'Длительность, мс',
            'chords_text' => 'Аккорды',
            'description' => 'Описание',
            'published_at' => 'Дата публикации',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getArtists(): ActiveQuery
    {
        return $this->hasMany(Artist::class, ['id' => 'artist_id'])
            ->viaTable('{{%recording_artist}}', ['recording_id' => 'id']);
    }

    public function getCoverMediaAsset(): ActiveQuery
    {
        return $this->hasOne(MediaAsset::class, ['id' => 'cover_media_asset_id']);
    }

    public function getMediaAssets(): ActiveQuery
    {
        return $this->hasMany(MediaAsset::class, ['id' => 'media_asset_id'])
            ->viaTable('{{%recording_media}}', ['recording_id' => 'id']);
    }

    public function getPublicationStatusList(): array
    {
        return [
            self::PUBLICATION_STATUS_DRAFT => 'Черновик',
            self::PUBLICATION_STATUS_PUBLISHED => 'Опубликована',
        ];
    }

    public function getRecordingTypeList(): array
    {
        return [
            self::TYPE_PERFORMANCE => 'Исполнение',
            self::TYPE_STUDIO => 'Студийная запись',
            self::TYPE_LIVE => 'Live',
            self::TYPE_VIDEO => 'Видео',
        ];
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }
}
