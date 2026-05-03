<?php

declare(strict_types=1);

namespace common\models;

use DateTimeImmutable;
use DateTimeZone;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $original_language_id
 * @property string $slug
 * @property string $default_title
 * @property string $publication_status
 * @property int|null $cover_media_asset_id
 * @property int|null $published_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Language $originalLanguage
 * @property MediaAsset|null $coverMediaAsset
 * @property SongLine[] $songLines
 * @property SongTranslation[] $translations
 */
final class Song extends ActiveRecord
{
    public const PUBLICATION_STATUS_DRAFT = 'draft';
    public const PUBLICATION_STATUS_PUBLISHED = 'published';

    public static function tableName(): string
    {
        return '{{%song}}';
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
            [['original_language_id', 'default_title'], 'required'],
            [
                ['cover_media_asset_id', 'published_at'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['original_language_id', 'cover_media_asset_id', 'published_at'], 'integer'],
            [['slug'], 'string', 'max' => 128],
            [['default_title'], 'string', 'max' => 255],
            [['publication_status'], 'string', 'max' => 32],
            [['publication_status'], 'default', 'value' => self::PUBLICATION_STATUS_DRAFT],
            [['publication_status'], 'in', 'range' => array_keys($this->getPublicationStatusList())],
            [['slug'], 'unique'],
            [['original_language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['original_language_id' => 'id']],
            [['cover_media_asset_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => MediaAsset::class, 'targetAttribute' => ['cover_media_asset_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'original_language_id' => 'Оригинальный язык',
            'slug' => 'Slug',
            'default_title' => 'Основное название',
            'publication_status' => 'Статус',
            'cover_media_asset_id' => 'Обложка',
            'published_at' => 'Дата публикации',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getPublicationStatusList(): array
    {
        return [
            self::PUBLICATION_STATUS_DRAFT => 'Черновик',
            self::PUBLICATION_STATUS_PUBLISHED => 'Опубликована',
        ];
    }

    public function getPublishedAtFormatted(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        return (new DateTimeImmutable('@' . (string) $this->published_at))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format('d.m.Y H:i:s');
    }

    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Artist::class, ['id' => 'artist_id'])->viaTable('{{%song_author}}', ['song_id' => 'id']);
    }

    public function getCoverMediaAsset(): ActiveQuery
    {
        return $this->hasOne(MediaAsset::class, ['id' => 'cover_media_asset_id']);
    }

    public function getOriginalLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'original_language_id']);
    }

    public function getRecordings(): ActiveQuery
    {
        return $this->hasMany(Recording::class, ['song_id' => 'id']);
    }

    public function getSongLines(): ActiveQuery
    {
        return $this->hasMany(SongLine::class, ['song_id' => 'id']);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->viaTable('{{%song_tag}}', ['song_id' => 'id']);
    }

    public function getTranslations(): ActiveQuery
    {
        return $this->hasMany(SongTranslation::class, ['song_id' => 'id']);
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert) === false) {
            return false;
        }

        $this->applyPublishedAtValue();

        return true;
    }

    private function applyPublishedAtValue(): void
    {
        if ($this->publication_status !== self::PUBLICATION_STATUS_PUBLISHED) {
            return;
        }

        if ($this->published_at !== null) {
            return;
        }

        $this->published_at = $this->getCurrentTimestamp();
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }
}
