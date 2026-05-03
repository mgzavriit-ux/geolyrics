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
 * @property string $slug
 * @property string $type
 * @property string $default_name
 * @property string $publication_status
 * @property int|null $published_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property ArtistTranslation[] $translations
 */
final class Artist extends ActiveRecord
{
    public const PUBLICATION_STATUS_DRAFT = 'draft';
    public const PUBLICATION_STATUS_PUBLISHED = 'published';

    public const TYPE_GROUP = 'group';
    public const TYPE_PERSON = 'person';

    public static function tableName(): string
    {
        return '{{%artist}}';
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
            [['slug', 'type', 'default_name'], 'required'],
            [
                ['published_at'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['published_at'], 'integer'],
            [['slug'], 'string', 'max' => 128],
            [['type', 'publication_status'], 'string', 'max' => 32],
            [['default_name'], 'string', 'max' => 255],
            [['publication_status'], 'default', 'value' => self::PUBLICATION_STATUS_DRAFT],
            [['type'], 'in', 'range' => array_keys($this->getTypeList())],
            [['publication_status'], 'in', 'range' => array_keys($this->getPublicationStatusList())],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug',
            'type' => 'Тип',
            'default_name' => 'Основное имя',
            'publication_status' => 'Статус',
            'published_at' => 'Дата публикации',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    public function getPublicationStatusList(): array
    {
        return [
            self::PUBLICATION_STATUS_DRAFT => 'Черновик',
            self::PUBLICATION_STATUS_PUBLISHED => 'Опубликован',
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

    public function getTypeList(): array
    {
        return [
            self::TYPE_PERSON => 'Персона',
            self::TYPE_GROUP => 'Группа',
        ];
    }

    public function getRecordings(): ActiveQuery
    {
        return $this->hasMany(Recording::class, ['id' => 'recording_id'])
            ->viaTable('{{%recording_artist}}', ['artist_id' => 'id']);
    }

    public function getSongs(): ActiveQuery
    {
        return $this->hasMany(Song::class, ['id' => 'song_id'])->viaTable('{{%song_author}}', ['artist_id' => 'id']);
    }

    public function getTranslations(): ActiveQuery
    {
        return $this->hasMany(ArtistTranslation::class, ['artist_id' => 'id']);
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
