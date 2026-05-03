<?php

declare(strict_types=1);

namespace common\models;

use DateTimeImmutable;
use DateTimeZone;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $song_id
 * @property string $slug
 * @property string $default_title
 * @property string $recording_type
 * @property string $publication_status
 * @property int|null $cover_media_asset_id
 * @property int|null $release_year
 * @property int|null $duration_ms
 * @property string|null $chords_text
 * @property string|null $description
 * @property int|null $published_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Song $song
 * @property MediaAsset|null $coverMediaAsset
 * @property RecordingArtist[] $recordingArtists
 * @property RecordingMedia[] $recordingMediaEntries
 */
final class Recording extends ActiveRecord
{
    public const string SCENARIO_EMBEDDED_SONG = 'embeddedSong';

    public const string PUBLICATION_STATUS_DRAFT = 'draft';
    public const string PUBLICATION_STATUS_PUBLISHED = 'published';

    public const string TYPE_AUDIO = 'audio';
    public const string TYPE_VIDEO = 'video';

    public static function tableName(): string
    {
        return '{{%recording}}';
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
            [['default_title', 'recording_type'], 'required'],
            [['song_id'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [
                ['cover_media_asset_id', 'release_year', 'duration_ms', 'published_at'],
                'filter',
                'filter' => static function ($value) {
                    if ($value === '') {
                        return null;
                    }

                    return $value;
                },
            ],
            [['song_id', 'cover_media_asset_id', 'release_year', 'duration_ms', 'published_at'], 'integer'],
            [['chords_text', 'description'], 'string'],
            [['slug'], 'string', 'max' => 128],
            [['default_title'], 'string', 'max' => 255],
            [['recording_type', 'publication_status'], 'string', 'max' => 32],
            [['publication_status'], 'default', 'value' => self::PUBLICATION_STATUS_DRAFT],
            [['recording_type'], 'in', 'range' => array_keys($this->getRecordingTypeList())],
            [['publication_status'], 'in', 'range' => array_keys($this->getPublicationStatusList())],
            [['slug'], 'unique'],
            [['song_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => Song::class, 'targetAttribute' => ['song_id' => 'id']],
            [['cover_media_asset_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => MediaAsset::class, 'targetAttribute' => ['cover_media_asset_id' => 'id']],
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_EMBEDDED_SONG] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'song_id' => 'Песня',
            'slug' => 'Slug',
            'default_title' => 'Основное название',
            'recording_type' => 'Тип записи',
            'publication_status' => 'Статус',
            'cover_media_asset_id' => 'Обложка',
            'release_year' => 'Год релиза',
            'duration_ms' => 'Длительность, мс',
            'chords_text' => 'Аккорды',
            'description' => 'Описание',
            'published_at' => 'Дата публикации',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function hasContent(): bool
    {
        if (trim((string) $this->slug) !== '') {
            return true;
        }

        if (trim((string) $this->default_title) !== '') {
            return true;
        }

        if (trim((string) $this->recording_type) !== '') {
            return true;
        }

        if (trim((string) $this->publication_status) !== '') {
            return true;
        }

        if ($this->cover_media_asset_id !== null) {
            return true;
        }

        if ($this->release_year !== null) {
            return true;
        }

        if ($this->duration_ms !== null) {
            return true;
        }

        if (trim((string) $this->chords_text) !== '') {
            return true;
        }

        if (trim((string) $this->description) !== '') {
            return true;
        }

        return $this->published_at !== null;
    }

    public function getArtists(): ActiveQuery
    {
        return $this->hasMany(Artist::class, ['id' => 'artist_id'])
            ->viaTable('{{%recording_artist}}', ['recording_id' => 'id']);
    }

    public function getAudioMediaAsset(): MediaAsset | null
    {
        return $this->findMediaAssetByRole(RecordingMedia::ROLE_AUDIO);
    }

    public function getCoverMediaAsset(): ActiveQuery
    {
        return $this->hasOne(MediaAsset::class, ['id' => 'cover_media_asset_id']);
    }

    public function getMediaAssets(): ActiveQuery
    {
        return $this->hasMany(MediaAsset::class, ['id' => 'media_asset_id'])
            ->viaTable('{{%recording_media}}', ['recording_id' => 'id']);
    }

    public function getRecordingArtists(): ActiveQuery
    {
        return $this->hasMany(RecordingArtist::class, ['recording_id' => 'id']);
    }

    public function getRecordingMediaEntries(): ActiveQuery
    {
        return $this->hasMany(RecordingMedia::class, ['recording_id' => 'id']);
    }

    public function getPublicationStatusList(): array
    {
        return [
            self::PUBLICATION_STATUS_DRAFT => 'Черновик',
            self::PUBLICATION_STATUS_PUBLISHED => 'Опубликована',
        ];
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function getPublishedAtFormatted(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        return (new DateTimeImmutable('@' . (string) $this->published_at))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format('d.m.Y H:i:s');
    }

    public function getRecordingTypeList(): array
    {
        return [
            self::TYPE_AUDIO => 'Аудио',
            self::TYPE_VIDEO => 'Видео',
        ];
    }

    public function getSong(): ActiveQuery
    {
        return $this->hasOne(Song::class, ['id' => 'song_id']);
    }

    public function getVideoMediaAsset(): MediaAsset | null
    {
        return $this->findMediaAssetByRole(RecordingMedia::ROLE_VIDEO);
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert) === false) {
            return false;
        }

        $this->applyPublishedAtValue();

        return true;
    }

    private function applyPublishedAtValue(): void
    {
        if ($this->publication_status !== self::PUBLICATION_STATUS_PUBLISHED) {
            return;
        }

        if ($this->published_at !== null) {
            return;
        }

        $this->published_at = $this->getCurrentTimestamp();
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }

    private function findMediaAssetByRole(string $role): MediaAsset | null
    {
        foreach ($this->recordingMediaEntries as $recordingMedia) {
            if ($recordingMedia->role !== $role) {
                continue;
            }

            return $recordingMedia->mediaAsset;
        }

        return null;
    }
}
