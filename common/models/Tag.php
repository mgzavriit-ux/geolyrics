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
 */
final class Tag extends ActiveRecord
{
    public const PUBLICATION_STATUS_DRAFT = 'draft';
    public const PUBLICATION_STATUS_PUBLISHED = 'published';

    public static function tableName(): string
    {
        return '{{%tag}}';
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

    public function getPublicationStatusList(): array
    {
        return [
            self::PUBLICATION_STATUS_DRAFT => 'Черновик',
            self::PUBLICATION_STATUS_PUBLISHED => 'Опубликован',
        ];
    }

    public function getSongs(): ActiveQuery
    {
        return $this->hasMany(Song::class, ['id' => 'song_id'])->viaTable('{{%song_tag}}', ['tag_id' => 'id']);
    }
}
