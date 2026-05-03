<?php

declare(strict_types=1);

namespace common\services;

use common\models\Recording;
use common\models\Song;
use yii\helpers\Inflector;

final class CatalogSlugGenerator
{
    private GeorgianTransliterator $transliterator;

    public function __construct(GeorgianTransliterator | null $transliterator = null)
    {
        $this->transliterator = $transliterator ?? new GeorgianTransliterator();
    }

    public function generateRecordingSlug(Recording $recording, Song $song): string
    {
        $songSlug = trim((string) $song->slug);

        if ($songSlug === '') {
            $songSlug = $this->generateSongSlug($song);
        }

        $recordingType = trim((string) $recording->recording_type);

        if ($songSlug === '' || $recordingType === '') {
            return '';
        }

        return $this->createUniqueRecordingSlug($songSlug . '-' . $recordingType, $recording->id);
    }

    public function generateSongSlug(Song $song): string
    {
        $baseSlug = $this->createBaseSlug((string) $song->default_title);

        if ($baseSlug === '') {
            return '';
        }

        return $this->createUniqueSongSlug($baseSlug, $song->id);
    }

    private function createBaseSlug(string $text): string
    {
        $transliteratedText = $this->transliterator->transliterateForSlug(trim($text));
        $slug = trim((string) Inflector::slug($transliteratedText, '-'), '-');

        return $slug;
    }

    private function createUniqueRecordingSlug(string $baseSlug, int | null $recordingId): string
    {
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->hasRecordingSlug($candidate, $recordingId)) {
            $candidate = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $candidate;
    }

    private function createUniqueSongSlug(string $baseSlug, int | null $songId): string
    {
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->hasSongSlug($candidate, $songId)) {
            $candidate = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $candidate;
    }

    private function hasRecordingSlug(string $slug, int | null $recordingId): bool
    {
        $query = Recording::find()->andWhere(['slug' => $slug]);

        if ($recordingId !== null) {
            $query->andWhere(['<>', 'id', $recordingId]);
        }

        return $query->exists();
    }

    private function hasSongSlug(string $slug, int | null $songId): bool
    {
        $query = Song::find()->andWhere(['slug' => $slug]);

        if ($songId !== null) {
            $query->andWhere(['<>', 'id', $songId]);
        }

        return $query->exists();
    }
}
