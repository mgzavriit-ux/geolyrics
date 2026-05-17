<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\FileHelper;

final class m260510_120000_import_legacy_video_files extends Migration
{
    public function safeUp(): void
    {
        $items = $this->findImportItems();

        if ($items === []) {
            return;
        }

        $timestamp = $this->getCurrentTimestamp();
        $copiedPaths = [];

        try {
            foreach ($items as $item) {
                $resolvedLegacyPath = $this->findResolvedLegacyPath($item['legacy_paths']);

                if ($resolvedLegacyPath === null) {
                    continue;
                }

                $sourceAbsolutePath = $this->createSourceAbsolutePath($resolvedLegacyPath);
                $targetPath = $this->createTargetPath(
                    $item['song_slug'],
                    $item['recording_slug'],
                    $resolvedLegacyPath,
                );
                $targetAbsolutePath = $this->createUploadAbsolutePath($targetPath);

                $this->copySourceFile(
                    $sourceAbsolutePath,
                    $targetAbsolutePath,
                    $copiedPaths,
                );

                $mediaAssetId = $this->upsertMediaAsset(
                    $targetPath,
                    $resolvedLegacyPath,
                    $sourceAbsolutePath,
                    $timestamp,
                );

                $this->replaceRecordingVideoMedia(
                    $item['recording_id'],
                    $mediaAssetId,
                );
                $this->clearLegacyDescription(
                    $item['recording_id'],
                    $item['recording_description'],
                    $timestamp,
                );
            }
        } catch (\Throwable $exception) {
            $this->rollbackCopiedFiles($copiedPaths);

            throw $exception;
        }
    }

    public function safeDown(): void
    {
        throw new RuntimeException('This migration is not reversible.');
    }

    /**
     * @return array<int, array{
     *     recording_id:int,
     *     recording_slug:string,
     *     recording_description:string,
     *     song_slug:string,
     *     legacy_paths:array<int, string>
     * }>
     */
    private function findImportItems(): array
    {
        $recordingRows = [];
        $songIds = [];

        $recordingQuery = (new Query())
            ->select(['id', 'song_id', 'slug', 'description'])
            ->from('{{%recording}}')
            ->andWhere(['recording_type' => 'video'])
            ->andWhere(['like', 'description', 'Legacy video path:']);

        foreach ($recordingQuery->each() as $row) {
            $description = (string) $row['description'];
            $legacyPaths = $this->extractLegacyPaths($description);

            if ($legacyPaths === []) {
                continue;
            }

            $recordingId = (int) $row['id'];
            $songId = (int) $row['song_id'];

            $recordingRows[] = [
                'recording_id' => $recordingId,
                'song_id' => $songId,
                'recording_slug' => (string) $row['slug'],
                'recording_description' => $description,
                'legacy_paths' => $legacyPaths,
            ];
            $songIds[$songId] = $songId;
        }

        if ($recordingRows === []) {
            return [];
        }

        $songSlugsById = $this->findSongSlugsById(array_values($songIds));
        $items = [];

        foreach ($recordingRows as $recordingRow) {
            $songSlug = $songSlugsById[$recordingRow['song_id']] ?? null;

            if ($songSlug === null) {
                continue;
            }

            $items[] = [
                'recording_id' => $recordingRow['recording_id'],
                'recording_slug' => $recordingRow['recording_slug'],
                'recording_description' => $recordingRow['recording_description'],
                'song_slug' => $songSlug,
                'legacy_paths' => $recordingRow['legacy_paths'],
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $songIds
     * @return array<int, string>
     */
    private function findSongSlugsById(array $songIds): array
    {
        if ($songIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'slug'])
            ->from('{{%song}}')
            ->andWhere(['id' => $songIds]);

        $songSlugsById = [];

        foreach ($query->each() as $row) {
            $songSlugsById[(int) $row['id']] = (string) $row['slug'];
        }

        return $songSlugsById;
    }

    /**
     * @return array<int, string>
     */
    private function extractLegacyPaths(string $description): array
    {
        $prefix = 'Legacy video path:';

        if (str_starts_with($description, $prefix) === false) {
            return [];
        }

        $pathsChunk = trim(substr($description, strlen($prefix)));

        if ($pathsChunk === '') {
            return [];
        }

        $paths = preg_split('/[\r\n;]+/', $pathsChunk) ?: [];
        $normalizedPaths = [];

        foreach ($paths as $path) {
            $normalizedPath = $this->normalizeLegacyPath((string) $path);

            if ($normalizedPath === null) {
                continue;
            }

            $normalizedPaths[] = $normalizedPath;
        }

        return array_values(array_unique($normalizedPaths));
    }

    private function normalizeLegacyPath(string $path): string | null
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '') {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', $normalizedPath);
        $normalizedPath = preg_replace('#/+#', '/', $normalizedPath);
        $normalizedPath = ltrim((string) $normalizedPath, '/');

        if ($normalizedPath === '') {
            return null;
        }

        return $normalizedPath;
    }

    /**
     * @param array<int, string> $legacyPaths
     */
    private function findResolvedLegacyPath(array $legacyPaths): string | null
    {
        foreach ($legacyPaths as $legacyPath) {
            foreach ($this->findCandidateLegacyPaths($legacyPath) as $candidateLegacyPath) {
                $sourceAbsolutePath = $this->createSourceAbsolutePath($candidateLegacyPath);

                if (is_file($sourceAbsolutePath) === true) {
                    return $candidateLegacyPath;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function findCandidateLegacyPaths(string $legacyPath): array
    {
        $aliases = $this->findLegacyPathAliases();

        if (isset($aliases[$legacyPath])) {
            return $aliases[$legacyPath];
        }

        $extension = pathinfo($legacyPath, PATHINFO_EXTENSION);

        if ($extension !== '') {
            return [$legacyPath];
        }

        $candidates = [];

        foreach (['mp4', 'm4v', 'mov', 'webm', 'avi', 'mkv'] as $candidateExtension) {
            $candidates[] = $legacyPath . '.' . $candidateExtension;
        }

        return $candidates;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function findLegacyPathAliases(): array
    {
        return [
            'melikishvili/basanoshkebi.mp4' => [
                'melikishvili/tsiteli_basanoshkebi.mp4',
            ],
            'khudzhadze/sad_gedzebo.mp4' => [
                'khudzhadze/sad_gedzeba.mp4',
            ],
        ];
    }

    private function createSourceAbsolutePath(string $legacyPath): string
    {
        return rtrim($this->getSourceBasePath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim($legacyPath, '/'));
    }

    private function createTargetPath(
        string $songSlug,
        string $recordingSlug,
        string $legacyPath,
    ): string {
        $extension = strtolower(pathinfo($legacyPath, PATHINFO_EXTENSION));
        $fileName = $recordingSlug;

        if ($extension !== '') {
            $fileName .= '.' . $extension;
        }

        return 'recordings/video/' . $songSlug . '/' . $fileName;
    }

    /**
     * @param array<int, string> $copiedPaths
     */
    private function copySourceFile(
        string $sourceAbsolutePath,
        string $targetAbsolutePath,
        array &$copiedPaths,
    ): void {
        if (is_file($sourceAbsolutePath) === false) {
            throw new RuntimeException('Legacy video file was not found: ' . $sourceAbsolutePath);
        }

        FileHelper::createDirectory(dirname($targetAbsolutePath));

        if (is_file($targetAbsolutePath) === true) {
            $sourceChecksum = hash_file('sha256', $sourceAbsolutePath);
            $targetChecksum = hash_file('sha256', $targetAbsolutePath);

            if ($sourceChecksum === $targetChecksum) {
                return;
            }

            throw new RuntimeException('Target video file already exists with different content: ' . $targetAbsolutePath);
        }

        if (copy($sourceAbsolutePath, $targetAbsolutePath) === false) {
            throw new RuntimeException('Failed to copy legacy video file into uploads: ' . $targetAbsolutePath);
        }

        $copiedPaths[] = $targetAbsolutePath;
    }

    private function upsertMediaAsset(
        string $targetPath,
        string $legacyPath,
        string $sourceAbsolutePath,
        int $timestamp,
    ): int {
        $existingMediaAsset = (new Query())
            ->select(['id'])
            ->from('{{%media_asset}}')
            ->andWhere([
                'storage' => 'local',
                'path' => $targetPath,
            ])
            ->one();

        $attributes = [
            'storage' => 'local',
            'path' => $targetPath,
            'original_name' => basename($legacyPath),
            'kind' => 'video',
            'mime_type' => $this->findMimeType($sourceAbsolutePath),
            'extension' => $this->findExtension($legacyPath),
            'size_bytes' => $this->findFileSize($sourceAbsolutePath),
            'checksum_sha256' => hash_file('sha256', $sourceAbsolutePath) ?: null,
            'duration_ms' => null,
            'width' => null,
            'height' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        if ($existingMediaAsset === false) {
            $this->insert('{{%media_asset}}', $attributes);

            return $this->findLastInsertId();
        }

        $mediaAssetId = (int) $existingMediaAsset['id'];

        unset($attributes['created_at']);

        $this->update('{{%media_asset}}', $attributes, ['id' => $mediaAssetId]);

        return $mediaAssetId;
    }

    private function replaceRecordingVideoMedia(int $recordingId, int $mediaAssetId): void
    {
        $existingMediaAssetIds = [];

        $query = (new Query())
            ->select(['media_asset_id'])
            ->from('{{%recording_media}}')
            ->andWhere([
                'recording_id' => $recordingId,
                'role' => 'video',
            ]);

        foreach ($query->each() as $row) {
            $existingMediaAssetIds[] = (int) $row['media_asset_id'];
        }

        $this->delete('{{%recording_media}}', [
            'recording_id' => $recordingId,
            'role' => 'video',
        ]);

        $this->insert('{{%recording_media}}', [
            'recording_id' => $recordingId,
            'media_asset_id' => $mediaAssetId,
            'role' => 'video',
            'sort_order' => 10,
            'is_primary' => true,
        ]);

        foreach ($existingMediaAssetIds as $existingMediaAssetId) {
            if ($existingMediaAssetId === $mediaAssetId) {
                continue;
            }

            $this->deleteOrphanedMediaAsset($existingMediaAssetId);
        }
    }

    private function deleteOrphanedMediaAsset(int $mediaAssetId): void
    {
        if ($this->hasRecordingMediaReference($mediaAssetId) === true) {
            return;
        }

        if ($this->hasRecordingCoverReference($mediaAssetId) === true) {
            return;
        }

        if ($this->hasSongCoverReference($mediaAssetId) === true) {
            return;
        }

        if ($this->hasArtistImageReference($mediaAssetId) === true) {
            return;
        }

        $mediaAsset = (new Query())
            ->select(['path', 'storage'])
            ->from('{{%media_asset}}')
            ->andWhere(['id' => $mediaAssetId])
            ->one();

        if ($mediaAsset === false) {
            return;
        }

        $this->delete('{{%media_asset}}', ['id' => $mediaAssetId]);

        if ((string) $mediaAsset['storage'] !== 'local') {
            return;
        }

        $absolutePath = $this->createUploadAbsolutePath((string) $mediaAsset['path']);

        if (is_file($absolutePath) === false) {
            return;
        }

        unlink($absolutePath);
    }

    private function hasRecordingMediaReference(int $mediaAssetId): bool
    {
        $exists = (new Query())
            ->from('{{%recording_media}}')
            ->andWhere(['media_asset_id' => $mediaAssetId])
            ->exists();

        return $exists;
    }

    private function hasRecordingCoverReference(int $mediaAssetId): bool
    {
        $exists = (new Query())
            ->from('{{%recording}}')
            ->andWhere(['cover_media_asset_id' => $mediaAssetId])
            ->exists();

        return $exists;
    }

    private function hasSongCoverReference(int $mediaAssetId): bool
    {
        $exists = (new Query())
            ->from('{{%song}}')
            ->andWhere(['cover_media_asset_id' => $mediaAssetId])
            ->exists();

        return $exists;
    }

    private function hasArtistImageReference(int $mediaAssetId): bool
    {
        $exists = (new Query())
            ->from('{{%artist_image}}')
            ->andWhere(['media_asset_id' => $mediaAssetId])
            ->exists();

        return $exists;
    }

    private function clearLegacyDescription(
        int $recordingId,
        string $recordingDescription,
        int $timestamp,
    ): void {
        $this->update(
            '{{%recording}}',
            [
                'description' => null,
                'updated_at' => $timestamp,
            ],
            [
                'id' => $recordingId,
                'description' => $recordingDescription,
            ],
        );
    }

    /**
     * @param array<int, string> $copiedPaths
     */
    private function rollbackCopiedFiles(array $copiedPaths): void
    {
        foreach (array_reverse($copiedPaths) as $copiedPath) {
            if (is_file($copiedPath) === false) {
                continue;
            }

            unlink($copiedPath);
        }
    }

    private function createUploadAbsolutePath(string $relativePath): string
    {
        return rtrim($this->getUploadsBasePath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
    }

    private function findMimeType(string $absolutePath): string | null
    {
        $mimeType = mime_content_type($absolutePath);

        if ($mimeType === false) {
            return null;
        }

        return $mimeType;
    }

    private function findExtension(string $legacyPath): string | null
    {
        $extension = strtolower(pathinfo($legacyPath, PATHINFO_EXTENSION));

        if ($extension === '') {
            return null;
        }

        return $extension;
    }

    private function findFileSize(string $absolutePath): int | null
    {
        $fileSize = filesize($absolutePath);

        if ($fileSize === false) {
            return null;
        }

        return $fileSize;
    }

    private function getUploadsBasePath(): string
    {
        return Yii::getAlias('@storage/uploads');
    }

    private function getSourceBasePath(): string
    {
        return Yii::getAlias('@storage/uploads/legacy-video-source');
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }

    private function findLastInsertId(): int
    {
        return (int) $this->db->getLastInsertID();
    }
}
