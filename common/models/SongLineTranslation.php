<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_line_id
 * @property int $language_id
 * @property string|null $translated_text
 * @property string $translation_source
 * @property string|null $provider
 * @property string|null $model
 * @property string $review_status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Language $language
 * @property SongLine $songLine
 */
final class SongLineTranslation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%song_line_translation}}';
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
            [['song_line_id', 'language_id'], 'integer'],
            [['translated_text'], 'string'],
            [['translation_source', 'review_status'], 'string', 'max' => 32],
            [['provider', 'model'], 'string', 'max' => 64],
            [['language_id'], 'required'],
            [['translation_source'], 'default', 'value' => 'manual'],
            [['review_status'], 'default', 'value' => 'approved'],
            [
                ['translated_text', 'provider', 'model'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['song_line_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => SongLine::class, 'targetAttribute' => ['song_line_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'translated_text' => 'Перевод строки',
        ];
    }

    public function getLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    public function getSongLine(): ActiveQuery
    {
        return $this->hasOne(SongLine::class, ['id' => 'song_line_id']);
    }

    public function hasContent(): bool
    {
        return trim((string) $this->translated_text) !== '';
    }
}
