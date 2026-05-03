<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property string $title
 * @property string $source_format
 * @property string $source_text
 * @property string|null $original_key
 * @property int|null $capo
 * @property string|null $parsed_payload
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Song $song
 */
final class SongArrangement extends ActiveRecord
{
    public const string FORMAT_CHORD_PRO = 'chordpro';
    public const string FORMAT_PLAIN_TEXT = 'plain_text';

    public static function tableName(): string
    {
        return '{{%song_arrangement}}';
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
            [['song_id', 'title', 'source_format', 'source_text'], 'required'],
            [
                ['capo'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['song_id', 'capo', 'sort_order'], 'integer'],
            [['source_text', 'parsed_payload'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['source_format'], 'string', 'max' => 32],
            [['original_key'], 'string', 'max' => 16],
            [['source_format'], 'default', 'value' => self::FORMAT_CHORD_PRO],
            [['sort_order'], 'default', 'value' => 100],
            [['source_format'], 'in', 'range' => array_keys($this->getSourceFormatList())],
            [['capo'], 'integer', 'min' => 0, 'max' => 24],
            [['song_id'], 'exist', 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название аранжировки',
            'source_format' => 'Формат',
            'source_text' => 'Текст аккордов',
            'original_key' => 'Тональность',
            'capo' => 'Каподастр',
        ];
    }

    public function hasContent(): bool
    {
        if (trim((string) $this->title) !== '') {
            return true;
        }

        if (trim((string) $this->source_text) !== '') {
            return true;
        }

        if (trim((string) $this->original_key) !== '') {
            return true;
        }

        return $this->capo !== null;
    }

    public function getParsedPayload(): array
    {
        if (trim((string) $this->parsed_payload) === '') {
            return [];
        }

        $payload = json_decode((string) $this->parsed_payload, true);

        if (is_array($payload) === false) {
            return [];
        }

        return $payload;
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }

    public function getSourceFormatList(): array
    {
        return [
            self::FORMAT_CHORD_PRO => 'ChordPro',
            self::FORMAT_PLAIN_TEXT => 'Простой текст',
        ];
    }
}
