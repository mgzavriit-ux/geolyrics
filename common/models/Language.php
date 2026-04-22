<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string|null $locale
 * @property string $name
 * @property string $native_name
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 */
final class Language extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%language}}';
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
            [['code', 'name', 'native_name'], 'required'],
            [
                ['locale'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['is_active', 'is_default'], 'boolean'],
            [['sort_order'], 'integer'],
            [['code', 'locale'], 'string', 'max' => 16],
            [['name', 'native_name'], 'string', 'max' => 64],
            [['sort_order'], 'default', 'value' => 100],
            [['is_active'], 'default', 'value' => true],
            [['is_default'], 'default', 'value' => false],
            [['code'], 'unique'],
            [['locale'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'code' => 'Код',
            'locale' => 'Locale',
            'name' => 'Название',
            'native_name' => 'Самоназвание',
            'is_active' => 'Активен',
            'is_default' => 'Язык по умолчанию',
            'sort_order' => 'Порядок',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    public function getSongs(): ActiveQuery
    {
        return $this->hasMany(Song::class, ['original_language_id' => 'id']);
    }
}
