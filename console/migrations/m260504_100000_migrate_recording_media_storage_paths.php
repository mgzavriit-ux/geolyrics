<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\FileHelper;

final class m260504_100000_migrate_recording_media_storage_paths extends Migration
{
    public function safeUp(): void
    {
        $items = $this->findRecordingMediaItemsToMigrate();

        if ($items === []) {
            return;
        }

        $timestamp = $this->getCurrentTimestamp();
        $movedFiles = [];

        try {
            foreach ($items as $item) {
                $targetPath = $this->createTargetPath(
                    $item['song_slug'],
                    $item['role'],
                    $item['path'],
                );

                $this->moveFile($item['path'], $targetPath);
                $movedFiles[] = [
                    'source_path' => $item['path'],
                    'target_path' => $targetPath,
                ];

                $this->update(
                    '{{%media_asset}}',
                    [
                        'path' => $targetPath,
                        'updated_at' => $timestamp,
                    ],
                    ['id' => $item['media_asset_id']],
                );
            }
        } catch (\Throwable $exception) {
            $this->rollbackMovedFiles($movedFiles);

            throw $exception;
        }
    }

    public function safeDown(): void
    {
        throw new RuntimeException('This migration is not reversible.');
    }

    /**
     * @return array<int, array{
     *     media_asset_id:int,
     *     path:string,
     *     role:string,
     *     recording_slug:string,
     *     song_slug:string
     * }>
     */
    private function findRecordingMediaItemsToMigrate(): array
    {
        $recordingMediaRows = [];
        $recordingIds = [];
        $mediaAssetIds = [];

        $recordingMediaQuery = (new Query())
            ->select(['recording_id', 'media_asset_id', 'role'])
            ->from('{{%recording_media}}')
            ->andWhere(['role' => ['audio', 'video']]);

        foreach ($recordingMediaQuery->each() as $row) {
            $recordingId = (int) $row['recording_id'];
            $mediaAssetId = (int) $row['media_asset_id'];

            $recordingMediaRows[] = [
                'recording_id' => $recordingId,
                'media_asset_id' => $mediaAssetId,
                'role' => (string) $row['role'],
            ];
            $recordingIds[$recordingId] = $recordingId;
            $mediaAssetIds[$mediaAssetId] = $mediaAssetId;
        }

        if ($recordingMediaRows === []) {
            return [];
        }

        $mediaAssetsById = $this->findMediaAssetsById(array_values($mediaAssetIds));
        $recordingsById = $this->findRecordingsById(array_values($recordingIds));
        $songsById = $this->findSongsById($this->findSongIds($recordingsById));
        $items = [];

        foreach ($recordingMediaRows as $recordingMediaRow) {
            $mediaAsset = $mediaAssetsById[$recordingMediaRow['media_asset_id']] ?? null;
            $recording = $recordingsById[$recordingMediaRow['recording_id']] ?? null;

            if ($mediaAsset === null || $recording === null) {
                continue;
            }

            $song = $songsById[$recording['song_id']] ?? null;

            if ($song === null) {
                continue;
            }

            if ($this->shouldMovePath($mediaAsset['path'], $recordingMediaRow['role']) === false) {
                continue;
            }

            $items[] = [
                'media_asset_id' => $recordingMediaRow['media_asset_id'],
                'path' => $mediaAsset['path'],
                'role' => $recordingMediaRow['role'],
                'recording_slug' => $recording['slug'],
                'song_slug' => $song['slug'],
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $mediaAssetIds
     * @return array<int, array{path:string, storage:string}>
     */
    private function findMediaAssetsById(array $mediaAssetIds): array
    {
        if ($mediaAssetIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'path', 'storage'])
            ->from('{{%media_asset}}')
            ->andWhere(['id' => $mediaAssetIds])
            ->andWhere(['storage' => 'local']);

        $mediaAssets = [];

        foreach ($query->each() as $row) {
            $mediaAssets[(int) $row['id']] = [
                'path' => (string) $row['path'],
                'storage' => (string) $row['storage'],
            ];
        }

        return $mediaAssets;
    }

    /**
     * @param array<int, int> $recordingIds
     * @return array<int, array{song_id:int, slug:string}>
     */
    private function findRecordingsById(array $recordingIds): array
    {
        if ($recordingIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'song_id', 'slug'])
            ->from('{{%recording}}')
            ->andWhere(['id' => $recordingIds]);

        $recordings = [];

        foreach ($query->each() as $row) {
            $recordings[(int) $row['id']] = [
                'song_id' => (int) $row['song_id'],
                'slug' => (string) $row['slug'],
            ];
        }

        return $recordings;
    }

    /**
     * @param array<int, array{song_id:int, slug:string}> $recordingsById
     * @return array<int, int>
     */
    private function findSongIds(array $recordingsById): array
    {
        $songIds = [];

        foreach ($recordingsById as $recording) {
            $songIds[$recording['song_id']] = $recording['song_id'];
        }

        return array_values($songIds);
    }

    /**
     * @param array<int, int> $songIds
     * @return array<int, array{slug:string}>
     */
    private function findSongsById(array $songIds): array
    {
        if ($songIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'slug'])
            ->from('{{%song}}')
            ->andWhere(['id' => $songIds]);

        $songs = [];

        foreach ($query->each() as $row) {
            $songs[(int) $row['id']] = [
                'slug' => (string) $row['slug'],
            ];
        }

        return $songs;
    }

    private function shouldMovePath(string $path, string $role): bool
    {
        $segments = $this->splitPath($path);

        if (count($segments) < 4) {
            return false;
        }

        if ($segments[0] !== 'recordings') {
            return false;
        }

        if (in_array($segments[1], ['audio', 'video', 'covers'], true)) {
            return false;
        }

        return $segments[2] === $role;
    }

    private function createTargetPath(string $songSlug, string $role, string $sourcePath): string
    {
        return 'recordings/'
            . $role
            . '/'
            . $songSlug
            . '/'
            . basename($sourcePath);
    }

    private function moveFile(string $sourcePath, string $targetPath): void
    {
        $sourceAbsolutePath = $this->findAbsolutePath($sourcePath);
        $targetAbsolutePath = $this->findAbsolutePath($targetPath);

        if (is_file($sourceAbsolutePath) === false) {
            throw new RuntimeException('Source media file was not found: ' . $sourcePath);
        }

        $targetDirectory = dirname($targetAbsolutePath);

        if (is_dir($targetDirectory) === false) {
            FileHelper::createDirectory($targetDirectory);
        }

        if (is_file($targetAbsolutePath)) {
            throw new RuntimeException('Target media file already exists: ' . $targetPath);
        }

        if (rename($sourceAbsolutePath, $targetAbsolutePath) === false) {
            throw new RuntimeException(
                'Cannot move media file from ' . $sourcePath . ' to ' . $targetPath . '.',
            );
        }
    }

    /**
     * @param array<int, array{source_path:string, target_path:string}> $movedFiles
     */
    private function rollbackMovedFiles(array $movedFiles): void
    {
        foreach (array_reverse($movedFiles) as $movedFile) {
            $sourceAbsolutePath = $this->findAbsolutePath($movedFile['source_path']);
            $targetAbsolutePath = $this->findAbsolutePath($movedFile['target_path']);

            if (is_file($targetAbsolutePath) === false) {
                continue;
            }

            $sourceDirectory = dirname($sourceAbsolutePath);

            if (is_dir($sourceDirectory) === false) {
                FileHelper::createDirectory($sourceDirectory);
            }

            rename($targetAbsolutePath, $sourceAbsolutePath);
        }
    }

    private function findAbsolutePath(string $relativePath): string
    {
        return rtrim($this->findStorageBasePath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    private function findStorageBasePath(): string
    {
        $basePath = getenv('STORAGE_BASE_PATH');

        if (is_string($basePath) && trim($basePath) !== '') {
            return trim($basePath);
        }

        return \Yii::getAlias('@storage/uploads');
    }

    /**
     * @return array<int, string>
     */
    private function splitPath(string $path): array
    {
        return array_values(array_filter(
            explode('/', trim(str_replace('\\', '/', $path), '/')),
            static fn (string $segment): bool => $segment !== '',
        ));
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }
}
