<?php

declare(strict_types=1);

namespace api\modules\v1\presenters;

use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\ArtistTranslation;
use common\models\Genre;
use common\models\GenreTranslation;
use common\models\Language;
use common\models\MediaAsset;
use common\models\Recording;
use common\models\RecordingArtist;
use common\models\RecordingMedia;
use common\models\Song;
use common\models\SongArrangement;
use common\models\SongLine;
use common\models\SongLineTranslation;
use common\models\SongTranslation;
use common\models\Tag;
use common\models\TagTranslation;
use common\services\GeorgianTransliterator;

final class SongPresenter
{
    private readonly GeorgianTransliterator $transliterator;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly bool $hasTitleTransliterations = true,
        GeorgianTransliterator | null $transliterator = null,
    ) {
        $this->transliterator = $transliterator ?? new GeorgianTransliterator();
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
            'artists' => $this->presentArtists($song),
            'genres' => $this->presentGenres($song),
            'tags' => $this->presentTags($song),
            'catalog' => $this->presentCatalogMeta($song),
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
            'transliterations' => $this->presentTransliterations($song->default_title),
            'cover' => $this->presentMediaAsset($song->coverMediaAsset),
            'artists' => $this->presentArtists($song),
            'genres' => $this->presentGenres($song),
            'tags' => $this->presentTags($song),
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
                'startMs' => $songLine->start_ms === null ? null : (int) $songLine->start_ms,
                'endMs' => $songLine->end_ms === null ? null : (int) $songLine->end_ms,
                'originalText' => $songLine->original_text,
                'translations' => $translations,
                'transliterations' => $this->presentTransliterations($songLine->original_text),
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

    private function presentArtistItem(Artist $artist): array
    {
        return [
            'id' => $artist->id,
            'slug' => $artist->slug,
            'name' => $this->presentArtistName($artist),
            'imageUrl' => $this->findArtistImageUrl($artist),
        ];
    }

    private function presentArtists(Song $song): array
    {
        $artists = [];
        $artistIds = [];

        foreach ($this->findPublishedRecordings($song) as $recording) {
            foreach ($this->findRecordingArtists($recording->recordingArtists) as $artist) {
                $artistId = (int) $artist->id;

                if (isset($artistIds[$artistId])) {
                    continue;
                }

                $artistIds[$artistId] = true;
                $artists[] = $this->presentArtistItem($artist);
            }
        }

        return $artists;
    }

    private function presentTagItem(Tag $tag): array
    {
        return [
            'id' => $tag->id,
            'slug' => $tag->slug,
            'name' => $this->presentCatalogName($tag->default_name, $tag->translations),
            'description' => $this->presentCatalogDescriptions($tag->translations),
        ];
    }

    private function presentGenreItem(Genre $genre): array
    {
        return [
            'id' => $genre->id,
            'slug' => $genre->slug,
            'name' => $this->presentCatalogName($genre->default_name, $genre->translations),
            'description' => $this->presentCatalogDescriptions($genre->translations),
        ];
    }

    /**
     * @param GenreTranslation[]|TagTranslation[] $translations
     * @return array<string, string>
     */
    private function presentCatalogName(string $defaultName, array $translations): array
    {
        return array_merge(
            ['default' => $defaultName],
            $this->presentCatalogNameTranslations($translations),
        );
    }

    /**
     * @param GenreTranslation[]|TagTranslation[] $translations
     * @return array<string, string>
     */
    private function presentCatalogNameTranslations(array $translations): array
    {
        $items = [];

        foreach ($translations as $translation) {
            $language = $translation->language;
            $name = trim((string) $translation->name);

            if ($language === null || $name === '') {
                continue;
            }

            $items[$language->code] = $name;
        }

        ksort($items);

        return $items;
    }

    /**
     * @param GenreTranslation[]|TagTranslation[] $translations
     * @return array<string, string>
     */
    private function presentCatalogDescriptions(array $translations): array
    {
        $items = [];

        foreach ($translations as $translation) {
            $language = $translation->language;
            $description = trim((string) $translation->description);

            if ($language === null || $description === '') {
                continue;
            }

            $items[$language->code] = $description;
        }

        ksort($items);

        return $items;
    }

    private function presentGenres(Song $song): array
    {
        $genres = [];

        foreach ($song->genres as $genre) {
            if ($genre->publication_status !== Genre::PUBLICATION_STATUS_PUBLISHED) {
                continue;
            }

            $genres[] = $this->presentGenreItem($genre);
        }

        return $genres;
    }

    private function presentTags(Song $song): array
    {
        $tags = [];

        foreach ($song->tags as $tag) {
            if ($tag->publication_status !== Tag::PUBLICATION_STATUS_PUBLISHED) {
                continue;
            }

            $tags[] = $this->presentTagItem($tag);
        }

        return $tags;
    }

    private function presentCatalogMeta(Song $song): array
    {
        $recordings = $this->findPublishedRecordings($song);
        $mainRecording = $recordings[0] ?? null;

        return [
            'durationMs' => $mainRecording?->duration_ms,
            'audio' => $this->presentMediaAsset($this->findRecordingMediaAsset($recordings, RecordingMedia::ROLE_AUDIO)),
            'hasAudio' => $this->hasRecordingMedia($recordings, RecordingMedia::ROLE_AUDIO),
            'hasVideo' => $this->hasRecordingMedia($recordings, RecordingMedia::ROLE_VIDEO),
            'hasChords' => count($song->songArrangements) > 0,
            'hasTranslation' => $this->hasTranslation($song),
            'hasTranscription' => $this->hasTranscription($song),
        ];
    }

    /**
     * @param RecordingArtist[] $recordingArtists
     * @return Artist[]
     */
    private function findRecordingArtists(array $recordingArtists): array
    {
        $artists = [];

        foreach ($this->sortRecordingArtistModels($recordingArtists) as $recordingArtist) {
            if ($recordingArtist->artist !== null) {
                $artists[] = $recordingArtist->artist;
            }
        }

        return $artists;
    }

    /**
     * @param RecordingArtist[] $recordingArtists
     * @return RecordingArtist[]
     */
    private function sortRecordingArtistModels(array $recordingArtists): array
    {
        usort($recordingArtists, static function (
            RecordingArtist $leftRecordingArtist,
            RecordingArtist $rightRecordingArtist,
        ): int {
            return ((int) $leftRecordingArtist->sort_order <=> (int) $rightRecordingArtist->sort_order)
                ?: ((int) $leftRecordingArtist->artist_id <=> (int) $rightRecordingArtist->artist_id);
        });

        return $recordingArtists;
    }

    private function findArtistImageUrl(Artist $artist): string | null
    {
        foreach ($artist->artistImages as $artistImage) {
            if ($artistImage->mediaAsset !== null) {
                return $this->storage->getPublicUrl($artistImage->mediaAsset->path);
            }
        }

        return null;
    }

    /**
     * @param Recording[] $recordings
     */
    private function findRecordingMediaAsset(array $recordings, string $role): MediaAsset | null
    {
        foreach ($recordings as $recording) {
            foreach ($recording->recordingMediaEntries as $recordingMedia) {
                if ($recordingMedia->role !== $role) {
                    continue;
                }

                if ($recordingMedia->mediaAsset instanceof MediaAsset) {
                    return $recordingMedia->mediaAsset;
                }
            }
        }

        return null;
    }

    /**
     * @param Recording[] $recordings
     */
    private function hasRecordingMedia(array $recordings, string $role): bool
    {
        foreach ($recordings as $recording) {
            foreach ($recording->recordingMediaEntries as $recordingMedia) {
                if ($recordingMedia->role === $role) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasTranslation(Song $song): bool
    {
        foreach ($song->translations as $songTranslation) {
            if ($songTranslation->hasContent()) {
                return true;
            }
        }

        foreach ($song->songLines as $songLine) {
            foreach ($songLine->translations as $songLineTranslation) {
                if ($songLineTranslation->hasContent()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasTranscription(Song $song): bool
    {
        if ($this->hasTitleTransliterations === false) {
            return false;
        }

        foreach ($song->titleTransliterations as $titleTransliteration) {
            if ($titleTransliteration->hasContent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function presentArtistName(Artist $artist): array
    {
        $translations = $this->presentArtistNameTranslations($artist->translations);

        return array_merge(
            ['default' => $artist->default_name],
            $translations,
        );
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
        $artists = [];

        foreach ($this->sortRecordingArtistModels($recording->recordingArtists) as $recordingArtist) {
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
                'transliterations' => $this->presentTransliterations($songArrangement->source_text),
                'parsedPayload' => $songArrangement->getParsedPayload(),
            ];
        }

        return $arrangements;
    }

    /**
     * @return array{cyrillic:string, latin:string}
     */
    private function presentTransliterations(string $text): array
    {
        return [
            'cyrillic' => $this->transliterator->transliterateByLanguageCode($text, 'ru'),
            'latin' => $this->transliterator->transliterateByLanguageCode($text, 'en'),
        ];
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
        return array_map(
            fn (Recording $recording): array => $this->presentRecording($recording),
            $this->findPublishedRecordings($song),
        );
    }

    /**
     * @return Recording[]
     */
    private function findPublishedRecordings(Song $song): array
    {
        $recordings = array_values(array_filter(
            $song->recordings,
            static fn (Recording $recording): bool => $recording->publication_status === Recording::PUBLICATION_STATUS_PUBLISHED,
        ));

        usort($recordings, static function (Recording $leftRecording, Recording $rightRecording): int {
            return $leftRecording->id <=> $rightRecording->id;
        });

        return $recordings;
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
