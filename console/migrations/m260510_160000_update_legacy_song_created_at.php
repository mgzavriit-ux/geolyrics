<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260510_160000_update_legacy_song_created_at extends Migration
{
    public function safeUp(): void
    {
        $songs = $this->findLegacySongs();

        if ($songs === []) {
            return;
        }

        $songIdsBySlug = $this->findSongIdsBySlug($this->findSongSlugs($songs));

        foreach ($songs as $song) {
            $songId = $songIdsBySlug[$song['slug']] ?? null;
            $createdAt = $this->findSongCreatedAt($song);

            if ($songId === null || $createdAt === null) {
                continue;
            }

            $this->update(
                '{{%song}}',
                ['created_at' => $createdAt],
                ['id' => $songId],
            );
        }
    }

    public function safeDown(): void
    {
        throw new RuntimeException('Migration is not reversible.');
    }

    /**
     * @return array<int, array{slug:string, created_at:int}>
     * @throws JsonException
     */
    private function findLegacySongs(): array
    {
        $contents = file_get_contents($this->getDataFilePath());

        if ($contents === false) {
            throw new RuntimeException('Cannot read legacy songs data file.');
        }

        /** @var array<int, array{slug:string, created_at:int}> $songs */
        $songs = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $songs;
    }

    /**
     * @param array<int, array{slug:string}> $songs
     * @return array<int, string>
     */
    private function findSongSlugs(array $songs): array
    {
        return array_column($songs, 'slug');
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, int>
     */
    private function findSongIdsBySlug(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $songIdsBySlug = (new Query())
            ->select(['id', 'slug'])
            ->from('{{%song}}')
            ->andWhere(['slug' => $slugs])
            ->indexBy('slug')
            ->column();

        return array_map(static fn ($value): int => (int) $value, $songIdsBySlug);
    }

    /**
     * @param array{created_at:int|string|null} $song
     */
    private function findSongCreatedAt(array $song): int | null
    {
        $createdAt = (int) ($song['created_at'] ?? 0);

        if ($createdAt <= 0) {
            return null;
        }

        return $createdAt;
    }

    private function getDataFilePath(): string
    {
        return __DIR__ . '/data/legacySongs.json';
    }
}
