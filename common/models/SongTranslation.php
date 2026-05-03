<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property int $language_id
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $description
 * @property string|null $history
 * @property string $translation_source
 * @property string|null $provider
 * @property string|null $model
 * @property string $review_status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Language $language
 * @property Song $song
 */
final class SongTranslation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%song_translation}}';
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
            [['song_id', 'language_id'], 'integer'],
            [['description', 'history'], 'string'],
            [['title', 'subtitle'], 'string', 'max' => 255],
            [['translation_source', 'review_status'], 'string', 'max' => 32],
            [['provider', 'model'], 'string', 'max' => 64],
            [['language_id'], 'required'],
            [['translation_source'], 'default', 'value' => 'manual'],
            [['review_status'], 'default', 'value' => 'approved'],
            [['title'], 'validateTitle'],
            [
                ['subtitle', 'description', 'history', 'provider', 'model'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['song_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название',
            'subtitle' => 'Подзаголовок',
            'description' => 'Описание',
            'history' => 'История песни',
        ];
    }

    public function getLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }

    public function hasContent(): bool
    {
        if (trim((string) $this->title) !== '') {
            return true;
        }

        if (trim((string) $this->subtitle) !== '') {
            return true;
        }

        if (trim((string) $this->description) !== '') {
            return true;
        }

        return trim((string) $this->history) !== '';
    }

    public function validateTitle(string $attribute): void
    {
        if ($this->hasContent() && trim((string) $this->$attribute) === '') {
            $this->addError($attribute, 'Укажите перевод названия.');
        }
    }
}
