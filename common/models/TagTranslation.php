<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $tag_id
 * @property int $language_id
 * @property string $name
 * @property string|null $description
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Language $language
 * @property Tag $tag
 */
final class TagTranslation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tag_translation}}';
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
            [['tag_id', 'language_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['language_id'], 'required'],
            [['name'], 'validateName'],
            [
                ['description'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['language_id'], 'exist', 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['tag_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Tag::class, 'targetAttribute' => ['tag_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название',
            'description' => 'Описание',
        ];
    }

    public function getLanguage(): ActiveQuery
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    public function getTag(): ActiveQuery
    {
        return $this->hasOne(Tag::class, ['id' => 'tag_id']);
    }

    public function hasContent(): bool
    {
        if (trim((string) $this->name) !== '') {
            return true;
        }

        return trim((string) $this->description) !== '';
    }

    public function validateName(string $attribute): void
    {
        if ($this->hasContent() && trim((string) $this->$attribute) === '') {
            $this->addError($attribute, 'Укажите название для перевода.');
        }
    }
}
