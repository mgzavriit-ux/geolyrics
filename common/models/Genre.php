<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $slug
 * @property string $default_name
 * @property string $publication_status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property GenreTranslation[] $translations
 */
final class Genre extends ActiveRecord
{
    public const string PUBLICATION_STATUS_DRAFT = 'draft';
    public const string PUBLICATION_STATUS_PUBLISHED = 'published';

    public static function tableName(): string
    {
        return '{{%genre}}';
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
            [['slug', 'default_name'], 'required'],
            [['slug'], 'string', 'max' => 128],
            [['default_name'], 'string', 'max' => 255],
            [['publication_status'], 'string', 'max' => 32],
            [['publication_status'], 'default', 'value' => self::PUBLICATION_STATUS_DRAFT],
            [['publication_status'], 'in', 'range' => array_keys($this->getPublicationStatusList())],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug',
            'default_name' => 'Название',
            'publication_status' => 'Статус',
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

    public function getSongs(): ActiveQuery
    {
        return $this->hasMany(Song::class, ['id' => 'song_id'])->viaTable('{{%song_genre}}', ['genre_id' => 'id']);
    }

    public function getTranslations(): ActiveQuery
    {
        return $this->hasMany(GenreTranslation::class, ['genre_id' => 'id']);
    }

    public function getNameByLanguageId(int $languageId): string
    {
        foreach ($this->translations as $translation) {
            if ((int) $translation->language_id !== $languageId) {
                continue;
            }

            $name = trim((string) $translation->name);

            if ($name === '') {
                break;
            }

            return $name;
        }

        return $this->default_name;
    }
}
