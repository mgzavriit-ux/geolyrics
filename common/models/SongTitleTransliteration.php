<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property string $system_code
 * @property string $system_name
 * @property string $transliterated_text
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Song $song
 */
final class SongTitleTransliteration extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%song_title_transliteration}}';
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
            [['song_id', 'system_code', 'system_name', 'transliterated_text'], 'required'],
            [['song_id'], 'integer'],
            [['transliterated_text'], 'string'],
            [['system_code'], 'string', 'max' => 32],
            [['system_name'], 'string', 'max' => 64],
            [['song_id'], 'exist', 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
        ];
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }

    public function hasContent(): bool
    {
        return trim($this->transliterated_text) !== '';
    }
}
