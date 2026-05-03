<?php

declare(strict_types=1);

namespace api\modules\v1\presenters;

use common\models\Artist;
use common\models\Recording;
use common\models\Song;
use common\models\SongTranslation;

final class ArtistPresenter
{
    public function presentListItem(Artist $artist): array
    {
        return [
            'id' => $artist->id,
            'slug' => $artist->slug,
            'type' => $artist->type,
            'defaultName' => $artist->default_name,
            'publicationStatus' => $artist->publication_status,
            'publishedAt' => $artist->published_at,
            'translations' => $this->presentArtistTranslations($artist),
        ];
    }

    public function presentDetail(Artist $artist): array
    {
        return [
            'id' => $artist->id,
            'slug' => $artist->slug,
            'type' => $artist->type,
            'defaultName' => $artist->default_name,
            'publicationStatus' => $artist->publication_status,
            'publishedAt' => $artist->published_at,
            'translations' => $this->presentArtistTranslations($artist),
            'songs' => $this->presentSongs($artist),
            'recordings' => $this->presentRecordings($artist),
        ];
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     slug:string,
     *     defaultTitle:string,
     *     translations:array<string, string>
     * }>
     */
    private function presentSongs(Artist $artist): array
    {
        $songsBySlug = [];

        foreach ($artist->recordings as $recording) {
            if ($recording->publication_status !== Recording::PUBLICATION_STATUS_PUBLISHED) {
                continue;
            }

            $song = $recording->song;

            if ($song === null || $song->publication_status !== Song::PUBLICATION_STATUS_PUBLISHED) {
                continue;
            }

            $songsBySlug[$song->slug] = [
                'id' => $song->id,
                'slug' => $song->slug,
                'defaultTitle' => $song->default_title,
                'translations' => $this->presentSongTitleTranslations($song->translations),
            ];
        }

        ksort($songsBySlug);

        return array_values($songsBySlug);
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     slug:string,
     *     defaultTitle:string,
     *     recordingType:string,
     *     publicationStatus:string,
     *     publishedAt:int|null,
     *     song:array{id:int, slug:string, defaultTitle:string}|null
     * }>
     */
    private function presentRecordings(Artist $artist): array
    {
        $recordings = array_values(array_filter(
            $artist->recordings,
            static fn (Recording $recording): bool => $recording->publication_status === Recording::PUBLICATION_STATUS_PUBLISHED,
        ));

        usort($recordings, static function (Recording $leftRecording, Recording $rightRecording): int {
            return $leftRecording->id <=> $rightRecording->id;
        });

        return array_map(
            fn (Recording $recording): array => [
                'id' => $recording->id,
                'slug' => $recording->slug,
                'defaultTitle' => $recording->default_title,
                'recordingType' => $recording->recording_type,
                'publicationStatus' => $recording->publication_status,
                'publishedAt' => $recording->published_at,
                'song' => $this->presentRecordingSong($recording->song),
            ],
            $recordings,
        );
    }

    /**
     * @return array<string, array{name:string, biography:string|null}>
     */
    private function presentArtistTranslations(Artist $artist): array
    {
        $translations = [];

        foreach ($artist->translations as $artistTranslation) {
            $language = $artistTranslation->language;

            if ($language === null) {
                continue;
            }

            $translations[$language->code] = [
                'name' => $artistTranslation->name,
                'biography' => $artistTranslation->biography,
            ];
        }

        ksort($translations);

        return $translations;
    }

    /**
     * @param SongTranslation[] $songTranslations
     * @return array<string, string>
     */
    private function presentSongTitleTranslations(array $songTranslations): array
    {
        $translations = [];

        foreach ($songTranslations as $songTranslation) {
            $language = $songTranslation->language;

            if ($language === null || trim((string) $songTranslation->title) === '') {
                continue;
            }

            $translations[$language->code] = $songTranslation->title;
        }

        ksort($translations);

        return $translations;
    }

    private function presentRecordingSong(Song | null $song): array | null
    {
        if ($song === null) {
            return null;
        }

        return [
            'id' => $song->id,
            'slug' => $song->slug,
            'defaultTitle' => $song->default_title,
        ];
    }
}
