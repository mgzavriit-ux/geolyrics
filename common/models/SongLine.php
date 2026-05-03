<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property string|null $section_code
 * @property int|null $section_number
 * @property int|null $sort_order
 * @property string $original_text
 * @property int|null $start_ms
 * @property int|null $end_ms
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Song $song
 * @property SongLineTranslation[] $translations
 */
final class SongLine extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%song_line}}';
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
            [['song_id', 'section_number', 'sort_order', 'start_ms', 'end_ms'], 'integer'],
            [['original_text'], 'string'],
            [['section_code'], 'string', 'max' => 32],
            [['original_text'], 'validateOriginalText'],
            [
                ['section_code', 'section_number', 'sort_order', 'start_ms', 'end_ms'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['song_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'section_code' => 'Секция',
            'section_number' => 'Номер секции',
            'sort_order' => 'Порядок',
            'original_text' => 'Оригинальный текст',
            'start_ms' => 'Старт, мс',
            'end_ms' => 'Финиш, мс',
        ];
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }

    public function getTranslations(): ActiveQuery
    {
        return $this->hasMany(SongLineTranslation::class, ['song_line_id' => 'id']);
    }

    public function hasContent(): bool
    {
        return trim((string) $this->original_text) !== '';
    }

    public function validateOriginalText(string $attribute): void
    {
        if ($this->hasContent() && trim((string) $this->$attribute) === '') {
            $this->addError($attribute, 'Укажите исходную строку песни.');
        }
    }
}
