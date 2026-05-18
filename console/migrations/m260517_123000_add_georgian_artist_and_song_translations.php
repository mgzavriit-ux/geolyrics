<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260517_123000_add_georgian_artist_and_song_translations extends Migration
{
    public function safeUp(): void
    {
        $timestamp = $this->getCurrentTimestamp();
        $englishLanguageId = $this->findEnglishLanguageId();
        $georgianLanguageId = $this->findGeorgianLanguageId();
        $legacyArtists = $this->findLegacyArtists();
        $legacySongs = $this->findLegacySongs();
        $transliterationRules = $this->findTransliterationRules($englishLanguageId);

        $this->upsertArtistTranslations(
            $legacyArtists,
            $georgianLanguageId,
            $timestamp,
        );
        $this->upsertSongTranslations(
            $legacySongs,
            $georgianLanguageId,
            $timestamp,
            $transliterationRules,
        );
    }

    public function safeDown(): void
    {
        echo "m260517_123000_add_georgian_artist_and_song_translations cannot be reverted.\n";
    }

    /**
     * @return array<int, array{
     *     slug:string,
     *     translations:array<string, array{name:string, biography:string|null}>
     * }>
     * @throws JsonException
     */
    private function findLegacyArtists(): array
    {
        $contents = file_get_contents($this->getArtistsDataFilePath());

        if ($contents === false) {
            throw new RuntimeException('Cannot read legacy artists data file.');
        }

        /** @var array<int, array{
         *     slug:string,
         *     translations:array<string, array{name:string, biography:string|null}>
         * }> $artists
         */
        $artists = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $artists;
    }

    private function findGeorgianLanguageId(): int
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => 'ka'])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            throw new RuntimeException('Georgian language was not found.');
        }

        return (int) $languageId;
    }

    private function findEnglishLanguageId(): int
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => 'en'])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            throw new RuntimeException('English language was not found.');
        }

        return (int) $languageId;
    }

    /**
     * @return array<int, array{
     *     slug:string,
     *     default_title:string
     * }>
     * @throws JsonException
     */
    private function findLegacySongs(): array
    {
        $contents = file_get_contents($this->getSongsDataFilePath());

        if ($contents === false) {
            throw new RuntimeException('Cannot read legacy songs data file.');
        }

        /** @var array<int, array{
         *     slug:string,
         *     default_title:string
         * }> $songs
         */
        $songs = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $songs;
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     translations:array<string, array{name:string, biography:string|null}>
     * }> $artists
     */
    private function upsertArtistTranslations(array $artists, int $languageId, int $timestamp): void
    {
        $artistIdsBySlug = $this->findArtistIdsBySlug(array_column($artists, 'slug'));
        $existingTranslationIdsByArtistId = $this->findArtistTranslationIdsByArtistId(
            array_values($artistIdsBySlug),
            $languageId,
        );

        foreach ($artists as $artist) {
            $artistId = $artistIdsBySlug[$artist['slug']] ?? null;
            $translation = $artist['translations']['ka'] ?? null;

            if ($artistId === null || is_array($translation) === false) {
                continue;
            }

            $name = trim((string) ($translation['name'] ?? ''));
            $biography = $this->normalizeNullableText($translation['biography'] ?? null);

            if ($name === '') {
                continue;
            }

            $translationId = $existingTranslationIdsByArtistId[$artistId] ?? null;
            $row = [
                'artist_id' => $artistId,
                'language_id' => $languageId,
                'name' => $name,
                'biography' => $biography,
                'updated_at' => $timestamp,
            ];

            if ($translationId === null) {
                $row['created_at'] = $timestamp;
                $this->insert('{{%artist_translation}}', $row);

                continue;
            }

            $this->update('{{%artist_translation}}', $row, ['id' => $translationId]);
        }
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     default_title:string
     * }> $songs
     */
    private function upsertSongTranslations(array $songs, int $languageId, int $timestamp, array $transliterationRules): void
    {
        $songIdsBySongIndex = $this->findSongIdsByLegacySongs($songs, $transliterationRules);
        $existingTranslationIdsBySongId = $this->findSongTranslationIdsBySongId(
            array_values($songIdsBySongIndex),
            $languageId,
        );

        foreach ($songs as $songIndex => $song) {
            $songId = $songIdsBySongIndex[$songIndex] ?? null;
            $title = trim((string) $song['default_title']);

            if ($songId === null || $title === '') {
                continue;
            }

            $translationId = $existingTranslationIdsBySongId[$songId] ?? null;
            $row = [
                'song_id' => $songId,
                'language_id' => $languageId,
                'title' => $title,
                'subtitle' => null,
                'description' => null,
                'history' => null,
                'translation_source' => 'manual',
                'provider' => null,
                'model' => null,
                'review_status' => 'approved',
                'updated_at' => $timestamp,
            ];

            if ($translationId === null) {
                $row['created_at'] = $timestamp;
                $this->insert('{{%song_translation}}', $row);

                continue;
            }

            $this->update('{{%song_translation}}', $row, ['id' => $translationId]);
        }
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, int>
     */
    private function findArtistIdsBySlug(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $artistIdsBySlug = (new Query())
            ->select(['id', 'slug'])
            ->from('{{%artist}}')
            ->andWhere(['slug' => $slugs])
            ->indexBy('slug')
            ->column();

        return array_map(static fn ($value): int => (int) $value, $artistIdsBySlug);
    }

    /**
     * @param array<int, int> $artistIds
     * @return array<int, int>
     */
    private function findArtistTranslationIdsByArtistId(array $artistIds, int $languageId): array
    {
        if ($artistIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'artist_id'])
            ->from('{{%artist_translation}}')
            ->andWhere(['artist_id' => $artistIds])
            ->andWhere(['language_id' => $languageId]);

        $translationIdsByArtistId = [];

        foreach ($query->each() as $row) {
            $translationIdsByArtistId[(int) $row['artist_id']] = (int) $row['id'];
        }

        return $translationIdsByArtistId;
    }

    private function getArtistsDataFilePath(): string
    {
        return __DIR__ . '/data/legacyArtists.json';
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }

    private function getSongsDataFilePath(): string
    {
        return __DIR__ . '/data/legacySongs.json';
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     default_title:string
     * }> $songs
     * @param array<string, string> $transliterationRules
     * @return array<int, int>
     */
    private function findSongIdsByLegacySongs(array $songs, array $transliterationRules): array
    {
        if ($songs === []) {
            return [];
        }

        $slugs = [];
        $transliteratedTitles = [];

        foreach ($songs as $song) {
            $slugs[] = $song['slug'];
            $transliteratedTitle = $this->transliterate((string) $song['default_title'], $transliterationRules);

            if ($transliteratedTitle !== '') {
                $transliteratedTitles[] = $transliteratedTitle;
            }
        }

        $rows = (new Query())
            ->select(['id', 'slug', 'default_title'])
            ->from('{{%song}}')
            ->andWhere([
                'or',
                ['slug' => array_values(array_unique($slugs))],
                ['default_title' => array_values(array_unique($transliteratedTitles))],
            ])
            ->all();

        $songIdsBySlug = [];
        $songIdsByTitle = [];

        foreach ($rows as $row) {
            $songId = (int) $row['id'];
            $songIdsBySlug[(string) $row['slug']] = $songId;
            $songIdsByTitle[(string) $row['default_title']] = $songId;
        }

        $songIdsBySongIndex = [];

        foreach ($songs as $songIndex => $song) {
            $slug = (string) $song['slug'];

            if (isset($songIdsBySlug[$slug])) {
                $songIdsBySongIndex[$songIndex] = $songIdsBySlug[$slug];
                continue;
            }

            $transliteratedTitle = $this->transliterate((string) $song['default_title'], $transliterationRules);

            if ($transliteratedTitle === '' || isset($songIdsByTitle[$transliteratedTitle]) === false) {
                continue;
            }

            $songIdsBySongIndex[$songIndex] = $songIdsByTitle[$transliteratedTitle];
        }

        return $songIdsBySongIndex;
    }

    private function normalizeNullableText(string | null $value): string | null
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return null;
        }

        return $normalizedValue;
    }

    /**
     * @return array<string, string>
     */
    private function findTransliterationRules(int $languageId): array
    {
        $rules = (new Query())
            ->select(['value', 'source_char'])
            ->from('{{%georgian_transliteration}}')
            ->andWhere(['target_language_id' => $languageId])
            ->indexBy('source_char')
            ->column();

        if (is_array($rules) === false) {
            return [];
        }

        return $rules;
    }

    /**
     * @param array<string, string> $rules
     */
    private function transliterate(string $text, array $rules): string
    {
        if ($text === '') {
            return '';
        }

        $result = [];

        foreach (mb_str_split($text) as $char) {
            $result[] = $rules[$char] ?? $char;
        }

        return implode('', $result);
    }

    /**
     * @param array<int, int> $songIds
     * @return array<int, int>
     */
    private function findSongTranslationIdsBySongId(array $songIds, int $languageId): array
    {
        if ($songIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'song_id'])
            ->from('{{%song_translation}}')
            ->andWhere(['song_id' => $songIds])
            ->andWhere(['language_id' => $languageId]);

        $translationIdsBySongId = [];

        foreach ($query->each() as $row) {
            $translationIdsBySongId[(int) $row['song_id']] = (int) $row['id'];
        }

        return $translationIdsBySongId;
    }
}
