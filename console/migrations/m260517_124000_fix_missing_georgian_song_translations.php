<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260517_124000_fix_missing_georgian_song_translations extends Migration
{
    public function safeUp(): void
    {
        $timestamp = time();
        $englishLanguageId = $this->findLanguageIdByCode('en');
        $georgianLanguageId = $this->findLanguageIdByCode('ka');
        $songs = $this->findLegacySongs();
        $transliterationRules = $this->findTransliterationRules($englishLanguageId);
        $songIdsBySongIndex = $this->findSongIdsByLegacySongs($songs, $transliterationRules);
        $existingTranslationIdsBySongId = $this->findSongTranslationIdsBySongId(
            array_values($songIdsBySongIndex),
            $georgianLanguageId,
        );

        foreach ($songs as $songIndex => $song) {
            $songId = $songIdsBySongIndex[$songIndex] ?? null;
            $title = trim((string) $song['default_title']);

            if ($songId === null || $title === '' || isset($existingTranslationIdsBySongId[$songId])) {
                continue;
            }

            $this->insert('{{%song_translation}}', [
                'song_id' => $songId,
                'language_id' => $georgianLanguageId,
                'title' => $title,
                'subtitle' => null,
                'description' => null,
                'history' => null,
                'translation_source' => 'manual',
                'provider' => null,
                'model' => null,
                'review_status' => 'approved',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function safeDown(): void
    {
        echo "m260517_124000_fix_missing_georgian_song_translations cannot be reverted.\n";
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
        $contents = file_get_contents(__DIR__ . '/data/legacySongs.json');

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

    private function findLanguageIdByCode(string $code): int
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => $code])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            throw new RuntimeException('Language "' . $code . '" was not found.');
        }

        return (int) $languageId;
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
}
