<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int|null $recording_id
 * @property int|null $artist_id
 * @property string|null $role
 * @property int|null $sort_order
 *
 * @property Artist $artist
 * @property Recording $recording
 */
final class RecordingArtist extends ActiveRecord
{
    public const ROLE_CHOIR = 'choir';
    public const ROLE_FEAT = 'feat';
    public const ROLE_PERFORMER = 'performer';

    public static function tableName(): string
    {
        return '{{%recording_artist}}';
    }

    public function rules(): array
    {
        return [
            [['recording_id', 'artist_id', 'sort_order'], 'integer'],
            [['role'], 'string', 'max' => 32],
            [['artist_id', 'role'], 'validateRequiredFields'],
            [
                ['recording_id', 'artist_id', 'sort_order', 'role'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['artist_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
            [['recording_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Recording::class, 'targetAttribute' => ['recording_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'artist_id' => 'Исполнитель',
            'role' => 'Роль',
            'sort_order' => 'Порядок',
        ];
    }

    public function getArtist(): ActiveQuery
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getRecording(): ActiveQuery
    {
        return $this->hasOne(Recording::class, ['id' => 'recording_id']);
    }

    public function getRoleList(): array
    {
        return [
            self::ROLE_PERFORMER => 'Основной исполнитель',
            self::ROLE_FEAT => 'Feat',
            self::ROLE_CHOIR => 'Хор / ансамбль',
        ];
    }

    public function hasContent(): bool
    {
        if ($this->artist_id !== null) {
            return true;
        }

        return trim((string) $this->role) !== '';
    }

    public function validateRequiredFields(string $attribute): void
    {
        if ($this->hasContent() === false) {
            return;
        }

        if ($attribute === 'artist_id' && $this->artist_id === null) {
            $this->addError($attribute, 'Выберите исполнителя.');
        }

        if ($attribute === 'role' && trim((string) $this->role) === '') {
            $this->addError($attribute, 'Укажите роль исполнителя.');
        }
    }
}
