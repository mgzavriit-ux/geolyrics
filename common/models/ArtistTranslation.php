<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $artist_id
 * @property int $language_id
 * @property string $name
 * @property string|null $biography
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Artist $artist
 * @property Language $language
 */
final class ArtistTranslation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%artist_translation}}';
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
            [['artist_id', 'language_id'], 'integer'],
            [['name', 'biography'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['language_id'], 'required'],
            [['name'], 'validateName'],
            [
                ['biography'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'artist_id' => 'Исполнитель',
            'language_id' => 'Язык',
            'name' => 'Название',
            'biography' => 'Биография',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    public function getArtist(): ActiveQuery
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    public function hasContent(): bool
    {
        if (trim((string) $this->name) !== '') {
            return true;
        }

        return trim((string) $this->biography) !== '';
    }

    public function validateName(string $attribute): void
    {
        if ($this->hasContent() && trim((string) $this->$attribute) === '') {
            $this->addError($attribute, 'Укажите название для перевода.');
        }
    }
}
