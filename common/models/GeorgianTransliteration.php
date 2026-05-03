<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $source_char
 * @property int $target_language_id
 * @property string $value
 *
 * @property Language $targetLanguage
 */
final class GeorgianTransliteration extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%georgian_transliteration}}';
    }

    public function rules(): array
    {
        return [
            [['source_char', 'target_language_id'], 'required'],
            [['target_language_id'], 'integer'],
            [['source_char'], 'string', 'max' => 8],
            [['value'], 'string', 'max' => 32],
            [
                ['value'],
                'filter',
                'filter' => static function ($value) {
                    return trim((string) $value);
                },
            ],
            [['source_char', 'target_language_id'], 'unique', 'targetAttribute' => ['source_char', 'target_language_id']],
            [['target_language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['target_language_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'source_char' => 'Грузинская буква',
            'target_language_id' => 'Язык',
            'value' => 'Транслитерация',
        ];
    }

    public function getTargetLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'target_language_id']);
    }
}
