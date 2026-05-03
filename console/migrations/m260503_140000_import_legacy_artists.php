<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260503_140000_import_legacy_artists extends Migration
{
    public function safeUp(): void
    {
        $artists = $this->findLegacyArtists();
        $timestamp = $this->getCurrentTimestamp();

        $this->upsertArtists($artists, $timestamp);
        $this->upsertArtistTranslations($artists, $timestamp);
    }

    public function safeDown(): void
    {
        $artistIdsBySlug = $this->findArtistIdsBySlug($this->findArtistSlugs($this->findLegacyArtists()));

        if ($artistIdsBySlug === []) {
            return;
        }

        $artistIds = array_values($artistIdsBySlug);

        $this->delete('{{%artist_translation}}', ['artist_id' => $artistIds]);
        $this->delete('{{%artist}}', ['id' => $artistIds]);
    }

    /**
     * @return array<int, array{
     *     legacy_category_id:int,
     *     slug:string,
     *     type:string,
     *     default_name:string,
     *     translations:array<string, array{name:string, biography:string|null}>
     * }>
     * @throws JsonException
     */
    private function findLegacyArtists(): array
    {
        $contents = file_get_contents($this->getDataFilePath());

        if ($contents === false) {
            throw new RuntimeException('Cannot read legacy artists data file.');
        }

        /** @var array<int, array{
         *     legacy_category_id:int,
         *     slug:string,
         *     type:string,
         *     default_name:string,
         *     translations:array<string, array{name:string, biography:string|null}>
         * }> $artists
         */
        $artists = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $artists;
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     type:string,
     *     default_name:string
     * }> $artists
     */
    private function upsertArtists(array $artists, int $timestamp): void
    {
        $existingArtists = $this->findExistingArtistsBySlug($this->findArtistSlugs($artists));

        foreach ($artists as $artist) {
            $slug = $artist['slug'];
            $existingArtist = $existingArtists[$slug] ?? null;
            $publishedAt = $existingArtist['published_at'] ?? $timestamp;

            if ($existingArtist === null) {
                $this->insert('{{%artist}}', [
                    'slug' => $slug,
                    'type' => $artist['type'],
                    'default_name' => $artist['default_name'],
                    'publication_status' => 'published',
                    'published_at' => $publishedAt,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                continue;
            }

            $this->update(
                '{{%artist}}',
                [
                    'type' => $artist['type'],
                    'default_name' => $artist['default_name'],
                    'publication_status' => 'published',
                    'published_at' => $publishedAt,
                    'updated_at' => $timestamp,
                ],
                ['id' => $existingArtist['id']],
            );
        }
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     translations:array<string, array{name:string, biography:string|null}>
     * }> $artists
     */
    private function upsertArtistTranslations(array $artists, int $timestamp): void
    {
        $artistIdsBySlug = $this->findArtistIdsBySlug($this->findArtistSlugs($artists));
        $languageIdsByCode = $this->findTranslationLanguageIdsByCode();
        $existingTranslations = $this->findExistingTranslationsByArtistAndLanguage(
            array_values($artistIdsBySlug),
            array_values($languageIdsByCode),
        );

        foreach ($artists as $artist) {
            $artistId = $artistIdsBySlug[$artist['slug']] ?? null;

            if ($artistId === null) {
                continue;
            }

            foreach ($artist['translations'] as $languageCode => $translation) {
                if ($languageCode === 'ka') {
                    continue;
                }

                $languageId = $languageIdsByCode[$languageCode] ?? null;

                if ($languageId === null || $translation['name'] === '') {
                    continue;
                }

                $translationKey = $this->createTranslationKey($artistId, $languageId);
                $existingTranslationId = $existingTranslations[$translationKey] ?? null;
                $row = [
                    'artist_id' => $artistId,
                    'language_id' => $languageId,
                    'name' => $translation['name'],
                    'biography' => $translation['biography'],
                    'updated_at' => $timestamp,
                ];

                if ($existingTranslationId === null) {
                    $row['created_at'] = $timestamp;
                    $this->insert('{{%artist_translation}}', $row);

                    continue;
                }

                $this->update('{{%artist_translation}}', $row, ['id' => $existingTranslationId]);
            }
        }
    }

    /**
     * @param array<int, array{slug:string}> $artists
     * @return array<int, string>
     */
    private function findArtistSlugs(array $artists): array
    {
        return array_column($artists, 'slug');
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, array{id:int, published_at:int|null}>
     */
    private function findExistingArtistsBySlug(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'slug', 'published_at'])
            ->from('{{%artist}}')
            ->andWhere(['slug' => $slugs]);

        $artists = [];

        foreach ($query->each() as $row) {
            $artists[(string) $row['slug']] = [
                'id' => (int) $row['id'],
                'published_at' => $row['published_at'] === null ? null : (int) $row['published_at'],
            ];
        }

        return $artists;
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
     * @return array<string, int>
     */
    private function findTranslationLanguageIdsByCode(): array
    {
        $languageIdsByCode = (new Query())
            ->select(['id', 'code'])
            ->from('{{%language}}')
            ->andWhere(['code' => ['ru', 'en']])
            ->indexBy('code')
            ->column();

        if (isset($languageIdsByCode['ru'], $languageIdsByCode['en'])) {
            return array_map(static fn ($value): int => (int) $value, $languageIdsByCode);
        }

        throw new RuntimeException('Required languages ru and en were not found.');
    }

    /**
     * @param array<int, int> $artistIds
     * @param array<int, int> $languageIds
     * @return array<string, int>
     */
    private function findExistingTranslationsByArtistAndLanguage(array $artistIds, array $languageIds): array
    {
        if ($artistIds === [] || $languageIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'artist_id', 'language_id'])
            ->from('{{%artist_translation}}')
            ->andWhere(['artist_id' => $artistIds])
            ->andWhere(['language_id' => $languageIds]);

        $translations = [];

        foreach ($query->each() as $row) {
            $translations[$this->createTranslationKey((int) $row['artist_id'], (int) $row['language_id'])] = (int) $row['id'];
        }

        return $translations;
    }

    private function createTranslationKey(int $artistId, int $languageId): string
    {
        return $artistId . ':' . $languageId;
    }

    private function getDataFilePath(): string
    {
        return __DIR__ . '/data/legacyArtists.json';
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }
}
