<?php

declare(strict_types=1);

namespace api\modules\v1\presenters;

use common\components\storage\StorageInterface;
use common\models\ArtistTranslation;
use common\models\Language;
use common\models\MediaAsset;
use common\models\Recording;
use common\models\RecordingArtist;
use common\models\Song;
use common\models\SongArrangement;
use common\models\SongLine;
use common\models\SongLineTranslation;
use common\models\SongTranslation;

final class SongPresenter
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function presentListItem(Song $song): array
    {
        return [
            'id' => $song->id,
            'slug' => $song->slug,
            'defaultTitle' => $song->default_title,
            'publicationStatus' => $song->publication_status,
            'publishedAt' => $song->published_at,
            'originalLanguage' => $this->presentLanguage($song->originalLanguage),
            'translations' => $this->presentSongTranslations($song),
            'cover' => $this->presentMediaAsset($song->coverMediaAsset),
        ];
    }

    public function presentDetail(Song $song): array
    {
        return [
            'id' => $song->id,
            'slug' => $song->slug,
            'defaultTitle' => $song->default_title,
            'publicationStatus' => $song->publication_status,
            'publishedAt' => $song->published_at,
            'originalLanguage' => $this->presentLanguage($song->originalLanguage),
            'translations' => $this->presentSongTranslations($song),
            'cover' => $this->presentMediaAsset($song->coverMediaAsset),
            'lyrics' => $this->presentLyrics($song),
            'arrangements' => $this->presentSongArrangements($song),
            'recordings' => $this->presentRecordings($song),
        ];
    }

    private function presentLanguage(Language | null $language): array | null
    {
        if ($language === null) {
            return null;
        }

        return [
            'code' => $language->code,
            'locale' => $language->locale,
            'name' => $language->name,
            'nativeName' => $language->native_name,
        ];
    }

    private function presentLyrics(Song $song): array
    {
        $songLines = $song->songLines;

        usort($songLines, static function (SongLine $leftSongLine, SongLine $rightSongLine): int {
            return ((int) $leftSongLine->sort_order <=> (int) $rightSongLine->sort_order)
                ?: ($leftSongLine->id <=> $rightSongLine->id);
        });

        $fullTexts = [];
        $lines = [];
        $originalLanguage = $song->originalLanguage;
        $originalLanguageCode = $originalLanguage === null ? 'ka' : $originalLanguage->code;

        foreach ($songLines as $songLine) {
            $translations = $this->presentSongLineTranslations($songLine);
            $fullTexts[$originalLanguageCode][] = $songLine->original_text;

            foreach (['ru', 'en', 'fr'] as $languageCode) {
                $fullTexts[$languageCode][] = $translations[$languageCode] ?? '';
            }

            $lines[] = [
                'sortOrder' => $songLine->sort_order,
                'originalText' => $songLine->original_text,
                'translations' => $translations,
            ];
        }

        return [
            'fullTexts' => $this->normalizeFullTexts($fullTexts),
            'lines' => $lines,
        ];
    }

    private function presentMediaAsset(MediaAsset | null $mediaAsset): array | null
    {
        if ($mediaAsset === null) {
            return null;
        }

        return [
            'id' => $mediaAsset->id,
            'kind' => $mediaAsset->kind,
            'path' => $mediaAsset->path,
            'url' => $this->storage->getPublicUrl($mediaAsset->path),
            'originalName' => $mediaAsset->original_name,
            'mimeType' => $mediaAsset->mime_type,
            'extension' => $mediaAsset->extension,
            'sizeBytes' => $mediaAsset->size_bytes,
            'durationMs' => $mediaAsset->duration_ms,
            'width' => $mediaAsset->width,
            'height' => $mediaAsset->height,
        ];
    }

    private function presentRecording(Recording $recording): array
    {
        return [
            'id' => $recording->id,
            'slug' => $recording->slug,
            'defaultTitle' => $recording->default_title,
            'recordingType' => $recording->recording_type,
            'publicationStatus' => $recording->publication_status,
            'publishedAt' => $recording->published_at,
            'releaseYear' => $recording->release_year,
            'durationMs' => $recording->duration_ms,
            'description' => $recording->description,
            'cover' => $this->presentMediaAsset($recording->coverMediaAsset),
            'media' => [
                'audio' => $this->presentMediaAsset($recording->getAudioMediaAsset()),
                'video' => $this->presentMediaAsset($recording->getVideoMediaAsset()),
            ],
            'artists' => $this->presentRecordingArtists($recording),
        ];
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     slug:string,
     *     defaultName:string,
     *     role:string|null,
     *     sortOrder:int|null,
     *     translations:array<string, string>
     * }>
     */
    private function presentRecordingArtists(Recording $recording): array
    {
        $recordingArtists = $recording->recordingArtists;

        usort($recordingArtists, static function (
            RecordingArtist $leftRecordingArtist,
            RecordingArtist $rightRecordingArtist,
        ): int {
            return ((int) $leftRecordingArtist->sort_order <=> (int) $rightRecordingArtist->sort_order)
                ?: ((int) $leftRecordingArtist->artist_id <=> (int) $rightRecordingArtist->artist_id);
        });

        $artists = [];

        foreach ($recordingArtists as $recordingArtist) {
            $artist = $recordingArtist->artist;

            if ($artist === null) {
                continue;
            }

            $artists[] = [
                'id' => $artist->id,
                'slug' => $artist->slug,
                'defaultName' => $artist->default_name,
                'role' => $recordingArtist->role,
                'sortOrder' => $recordingArtist->sort_order,
                'translations' => $this->presentArtistNameTranslations($artist->translations),
            ];
        }

        return $artists;
    }

    /**
     * @param SongArrangement[] $songArrangements
     * @return array<int, array{
     *     id:int,
     *     title:string,
     *     sourceFormat:string,
     *     sourceText:string,
     *     originalKey:string|null,
     *     capo:int|null,
     *     sortOrder:int,
     *     parsedPayload:array
     * }>
     */
    private function presentSongArrangements(Song $song): array
    {
        $arrangements = [];

        foreach ($song->songArrangements as $songArrangement) {
            $arrangements[] = [
                'id' => $songArrangement->id,
                'title' => $songArrangement->title,
                'sourceFormat' => $songArrangement->source_format,
                'sourceText' => $songArrangement->source_text,
                'originalKey' => $songArrangement->original_key,
                'capo' => $songArrangement->capo,
                'sortOrder' => $songArrangement->sort_order,
                'parsedPayload' => $songArrangement->getParsedPayload(),
            ];
        }

        return $arrangements;
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     slug:string,
     *     defaultTitle:string,
     *     recordingType:string,
     *     publicationStatus:string,
     *     publishedAt:int|null,
     *     releaseYear:int|null,
     *     durationMs:int|null,
     *     description:string|null,
     *     cover:array|null,
     *     media:array{audio:array|null, video:array|null},
     *     artists:array
     * }>
     */
    private function presentRecordings(Song $song): array
    {
        $recordings = array_values(array_filter(
            $song->recordings,
            static fn (Recording $recording): bool => $recording->publication_status === Recording::PUBLICATION_STATUS_PUBLISHED,
        ));

        usort($recordings, static function (Recording $leftRecording, Recording $rightRecording): int {
            return ($leftRecording->id <=> $rightRecording->id);
        });

        return array_map(
            fn (Recording $recording): array => $this->presentRecording($recording),
            $recordings,
        );
    }

    /**
     * @param SongLineTranslation[] $songLineTranslations
     * @return array<string, string>
     */
    private function presentSongLineTranslations(SongLine $songLine): array
    {
        $translations = [];

        foreach ($songLine->translations as $songLineTranslation) {
            $language = $songLineTranslation->language;

            if ($language === null || trim((string) $songLineTranslation->translated_text) === '') {
                continue;
            }

            $translations[$language->code] = $songLineTranslation->translated_text;
        }

        ksort($translations);

        return $translations;
    }

    /**
     * @return array<string, array{
     *     title:string,
     *     subtitle:string|null,
     *     description:string|null,
     *     history:string|null
     * }>
     */
    private function presentSongTranslations(Song $song): array
    {
        $translations = [];

        foreach ($song->translations as $songTranslation) {
            $language = $songTranslation->language;

            if ($language === null) {
                continue;
            }

            $translations[$language->code] = [
                'title' => $songTranslation->title,
                'subtitle' => $songTranslation->subtitle,
                'description' => $songTranslation->description,
                'history' => $songTranslation->history,
            ];
        }

        ksort($translations);

        return $translations;
    }

    /**
     * @param ArtistTranslation[] $artistTranslations
     * @return array<string, string>
     */
    private function presentArtistNameTranslations(array $artistTranslations): array
    {
        $translations = [];

        foreach ($artistTranslations as $artistTranslation) {
            $language = $artistTranslation->language;

            if ($language === null || trim((string) $artistTranslation->name) === '') {
                continue;
            }

            $translations[$language->code] = $artistTranslation->name;
        }

        ksort($translations);

        return $translations;
    }

    /**
     * @param array<string, array<int, string>> $fullTexts
     * @return array<string, string>
     */
    private function normalizeFullTexts(array $fullTexts): array
    {
        $normalizedFullTexts = [];

        foreach ($fullTexts as $languageCode => $lines) {
            $normalizedFullTexts[$languageCode] = implode("\n", $lines);
        }

        ksort($normalizedFullTexts);

        return $normalizedFullTexts;
    }
}
