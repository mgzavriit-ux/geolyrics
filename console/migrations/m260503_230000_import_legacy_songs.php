<?php

declare(strict_types=1);

use common\services\ChordSheetParser;
use yii\db\Migration;
use yii\db\Query;

final class m260503_230000_import_legacy_songs extends Migration
{
    private ChordSheetParser | null $chordSheetParser = null;

    public function safeUp(): void
    {
        $songs = $this->findLegacySongs();

        if ($songs === []) {
            return;
        }

        $timestamp = $this->getCurrentTimestamp();
        $languageIdsByCode = $this->findLanguageIdsByCode();
        $artistIdsBySlug = $this->findArtistIdsBySlug($this->findArtistSlugs($songs));
        $songIdsBySlug = $this->upsertSongs(
            $songs,
            $languageIdsByCode['ka'],
            $timestamp,
        );

        $this->upsertSongTranslations(
            $songs,
            $songIdsBySlug,
            $languageIdsByCode,
            $timestamp,
        );
        $this->replaceSongLines(
            $songs,
            $songIdsBySlug,
            $languageIdsByCode,
            $timestamp,
        );
        $this->replaceSongArrangements(
            $songs,
            $songIdsBySlug,
            $timestamp,
        );
        $this->upsertRecordings(
            $songs,
            $songIdsBySlug,
            $artistIdsBySlug,
            $timestamp,
        );
    }

    public function safeDown(): void
    {
        $songSlugs = $this->findSongSlugs($this->findLegacySongs());

        if ($songSlugs === []) {
            return;
        }

        $this->delete('{{%song}}', ['slug' => $songSlugs]);
    }

    /**
     * @return array<int, array{
     *     slug:string,
     *     created_at:int,
     *     publication_status:string,
     *     default_title:string,
     *     translations:array<string, array{title:string, history_html:string}>,
     *     full_texts:array<string, string>,
     *     arrangements:array<int, array{
     *         title:string,
     *         source_format:string,
     *         source_text:string,
     *         original_key:string|null,
     *         capo:int|null
     *     }>,
     *     artist_slugs:array<int, string>,
     *     recordings:array<int, array{
     *         slug:string,
     *         recording_type:string,
     *         default_title:string,
     *         publication_status:string,
     *         artist_slugs:array<int, string>,
     *         legacy_path:string
     *     }>
     * }>
     * @throws JsonException
     */
    private function findLegacySongs(): array
    {
        $contents = file_get_contents($this->getDataFilePath());

        if ($contents === false) {
            throw new RuntimeException('Cannot read legacy songs data file.');
        }

        /** @var array<int, array{
         *     slug:string,
         *     created_at:int,
         *     publication_status:string,
         *     default_title:string,
         *     translations:array<string, array{title:string, history_html:string}>,
         *     full_texts:array<string, string>,
         *     arrangements:array<int, array{
         *         title:string,
         *         source_format:string,
         *         source_text:string,
         *         original_key:string|null,
         *         capo:int|null
         *     }>,
         *     artist_slugs:array<int, string>,
         *     recordings:array<int, array{
         *         slug:string,
         *         recording_type:string,
         *         default_title:string,
         *         publication_status:string,
         *         artist_slugs:array<int, string>,
         *         legacy_path:string
         *     }>
         * }> $songs
         */
        $songs = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $songs;
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     created_at:int,
     *     publication_status:string,
     *     default_title:string
     * }> $songs
     * @return array<string, int>
     */
    private function upsertSongs(array $songs, int $originalLanguageId, int $timestamp): array
    {
        $existingSongsBySlug = $this->findExistingSongsBySlug($this->findSongSlugs($songs));
        $songIdsBySlug = [];

        foreach ($songs as $song) {
            $slug = $song['slug'];
            $existingSong = $existingSongsBySlug[$slug] ?? null;
            $createdAt = $this->findSongCreatedAt($song, $timestamp);
            $publishedAt = $this->createPublishedAtValue(
                $song['publication_status'],
                $existingSong['published_at'] ?? null,
                $timestamp,
            );

            if ($existingSong === null) {
                $this->insert('{{%song}}', [
                    'original_language_id' => $originalLanguageId,
                    'slug' => $slug,
                    'default_title' => $song['default_title'],
                    'publication_status' => $song['publication_status'],
                    'cover_media_asset_id' => null,
                    'published_at' => $publishedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $timestamp,
                ]);

                $songIdsBySlug[$slug] = $this->findLastInsertId();
                continue;
            }

            $this->update(
                '{{%song}}',
                [
                    'original_language_id' => $originalLanguageId,
                    'default_title' => $song['default_title'],
                    'publication_status' => $song['publication_status'],
                    'published_at' => $publishedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $timestamp,
                ],
                ['id' => $existingSong['id']],
            );

            $songIdsBySlug[$slug] = $existingSong['id'];
        }

        return $songIdsBySlug;
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     default_title:string,
     *     translations:array<string, array{title:string, history_html:string}>
     * }> $songs
     * @param array<string, int> $songIdsBySlug
     * @param array<string, int> $languageIdsByCode
     */
    private function upsertSongTranslations(
        array $songs,
        array $songIdsBySlug,
        array $languageIdsByCode,
        int $timestamp,
    ): void {
        $songIds = array_values($songIdsBySlug);
        $translationLanguageIds = [
            $languageIdsByCode['ru'],
            $languageIdsByCode['en'],
            $languageIdsByCode['fr'],
        ];
        $existingTranslations = $this->findExistingSongTranslationsBySongAndLanguage(
            $songIds,
            $translationLanguageIds,
        );

        foreach ($songs as $song) {
            $songId = $songIdsBySlug[$song['slug']] ?? null;

            if ($songId === null) {
                continue;
            }

            foreach (['ru', 'en', 'fr'] as $languageCode) {
                $translation = $song['translations'][$languageCode] ?? null;

                if (is_array($translation) === false) {
                    continue;
                }

                $title = trim((string) ($translation['title'] ?? ''));
                $history = $this->normalizeNullableText($translation['history_html'] ?? null);

                if ($title === '') {
                    continue;
                }

                $languageId = $languageIdsByCode[$languageCode] ?? null;

                if ($languageId === null) {
                    continue;
                }

                $translationKey = $this->createSongLanguageKey($songId, $languageId);
                $existingTranslationId = $existingTranslations[$translationKey] ?? null;
                $row = [
                    'song_id' => $songId,
                    'language_id' => $languageId,
                    'title' => $title,
                    'subtitle' => null,
                    'description' => null,
                    'history' => $history,
                    'translation_source' => 'manual',
                    'provider' => null,
                    'model' => null,
                    'review_status' => 'approved',
                    'updated_at' => $timestamp,
                ];

                if ($existingTranslationId === null) {
                    $row['created_at'] = $timestamp;
                    $this->insert('{{%song_translation}}', $row);

                    continue;
                }

                $this->update('{{%song_translation}}', $row, ['id' => $existingTranslationId]);
            }
        }
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     full_texts:array<string, string>
     * }> $songs
     * @param array<string, int> $songIdsBySlug
     * @param array<string, int> $languageIdsByCode
     */
    private function replaceSongLines(
        array $songs,
        array $songIdsBySlug,
        array $languageIdsByCode,
        int $timestamp,
    ): void {
        $songIds = array_values($songIdsBySlug);

        if ($songIds !== []) {
            $this->delete('{{%song_line}}', ['song_id' => $songIds]);
        }

        foreach ($songs as $song) {
            $songId = $songIdsBySlug[$song['slug']] ?? null;

            if ($songId === null) {
                continue;
            }

            $originalLines = $this->splitFullTextLines($song['full_texts']['ka'] ?? '');

            if ($originalLines === []) {
                continue;
            }

            $translatedLinesByLanguage = [
                'ru' => $this->alignTranslatedLines(
                    $originalLines,
                    $song['full_texts']['ru'] ?? '',
                ),
                'en' => $this->alignTranslatedLines(
                    $originalLines,
                    $song['full_texts']['en'] ?? '',
                ),
                'fr' => $this->alignTranslatedLines(
                    $originalLines,
                    $song['full_texts']['fr'] ?? '',
                ),
            ];
            $sortOrder = 10;

            foreach ($originalLines as $lineIndex => $originalText) {
                $this->insert('{{%song_line}}', [
                    'song_id' => $songId,
                    'section_code' => null,
                    'section_number' => null,
                    'sort_order' => $sortOrder,
                    'original_text' => $originalText,
                    'start_ms' => null,
                    'end_ms' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $songLineId = $this->findLastInsertId();
                $sortOrder += 10;

                foreach (['ru', 'en', 'fr'] as $languageCode) {
                    $translatedText = trim((string) ($translatedLinesByLanguage[$languageCode][$lineIndex] ?? ''));

                    if ($translatedText === '') {
                        continue;
                    }

                    $languageId = $languageIdsByCode[$languageCode] ?? null;

                    if ($languageId === null) {
                        continue;
                    }

                    $this->insert('{{%song_line_translation}}', [
                        'song_line_id' => $songLineId,
                        'language_id' => $languageId,
                        'translated_text' => $translatedText,
                        'translation_source' => 'manual',
                        'provider' => null,
                        'model' => null,
                        'review_status' => 'approved',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }
        }
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     arrangements:array<int, array{
     *         title:string,
     *         source_format:string,
     *         source_text:string,
     *         original_key:string|null,
     *         capo:int|null
     *     }>
     * }> $songs
     * @param array<string, int> $songIdsBySlug
     * @throws JsonException
     */
    private function replaceSongArrangements(
        array $songs,
        array $songIdsBySlug,
        int $timestamp,
    ): void {
        $songIds = array_values($songIdsBySlug);

        if ($songIds !== []) {
            $this->delete('{{%song_arrangement}}', ['song_id' => $songIds]);
        }

        $chordSheetParser = $this->getChordSheetParser();

        foreach ($songs as $song) {
            $songId = $songIdsBySlug[$song['slug']] ?? null;

            if ($songId === null) {
                continue;
            }

            $sortOrder = 10;

            foreach ($song['arrangements'] as $arrangement) {
                $sourceText = trim((string) ($arrangement['source_text'] ?? ''));

                if ($sourceText === '') {
                    continue;
                }

                $sourceFormat = trim((string) ($arrangement['source_format'] ?? 'plain_text'));
                $parsedPayload = $chordSheetParser->parse($sourceFormat, $sourceText);

                $this->insert('{{%song_arrangement}}', [
                    'song_id' => $songId,
                    'title' => trim((string) ($arrangement['title'] ?? 'Аранжировка')),
                    'source_format' => $sourceFormat,
                    'source_text' => $sourceText,
                    'original_key' => $this->normalizeNullableText($arrangement['original_key'] ?? null),
                    'capo' => $arrangement['capo'] ?? null,
                    'parsed_payload' => json_encode(
                        $parsedPayload,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ),
                    'sort_order' => $sortOrder,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $sortOrder += 10;
            }
        }
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     recordings:array<int, array{
     *         slug:string,
     *         recording_type:string,
     *         default_title:string,
     *         publication_status:string,
     *         artist_slugs:array<int, string>,
     *         legacy_path:string
     *     }>
     * }> $songs
     * @param array<string, int> $songIdsBySlug
     * @param array<string, int> $artistIdsBySlug
     */
    private function upsertRecordings(
        array $songs,
        array $songIdsBySlug,
        array $artistIdsBySlug,
        int $timestamp,
    ): void {
        $recordings = $this->findImportRecordings($songs, $songIdsBySlug);
        $existingRecordingsBySlug = $this->findExistingRecordingsBySlug(
            $this->findRecordingSlugs($recordings),
        );
        $recordingIdsBySlug = [];

        foreach ($recordings as $recording) {
            $existingRecording = $existingRecordingsBySlug[$recording['slug']] ?? null;
            $publishedAt = $this->createPublishedAtValue(
                $recording['publication_status'],
                $existingRecording['published_at'] ?? null,
                $timestamp,
            );
            $description = $this->createLegacyMediaDescription(
                $recording['recording_type'],
                $recording['legacy_path'],
            );

            if ($existingRecording === null) {
                $this->insert('{{%recording}}', [
                    'song_id' => $recording['song_id'],
                    'slug' => $recording['slug'],
                    'default_title' => $recording['default_title'],
                    'recording_type' => $recording['recording_type'],
                    'publication_status' => $recording['publication_status'],
                    'cover_media_asset_id' => null,
                    'release_year' => null,
                    'duration_ms' => null,
                    'description' => $description,
                    'published_at' => $publishedAt,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $recordingIdsBySlug[$recording['slug']] = $this->findLastInsertId();
                continue;
            }

            $this->update(
                '{{%recording}}',
                [
                    'song_id' => $recording['song_id'],
                    'default_title' => $recording['default_title'],
                    'recording_type' => $recording['recording_type'],
                    'publication_status' => $recording['publication_status'],
                    'description' => $description,
                    'published_at' => $publishedAt,
                    'updated_at' => $timestamp,
                ],
                ['id' => $existingRecording['id']],
            );

            $recordingIdsBySlug[$recording['slug']] = $existingRecording['id'];
        }

        $recordingIds = array_values($recordingIdsBySlug);

        if ($recordingIds !== []) {
            $this->delete('{{%recording_artist}}', ['recording_id' => $recordingIds]);
        }

        foreach ($recordings as $recording) {
            $recordingId = $recordingIdsBySlug[$recording['slug']] ?? null;

            if ($recordingId === null) {
                continue;
            }

            $sortOrder = 10;

            foreach ($recording['artist_slugs'] as $artistSlug) {
                $artistId = $artistIdsBySlug[$artistSlug] ?? null;

                if ($artistId === null) {
                    continue;
                }

                $this->insert('{{%recording_artist}}', [
                    'recording_id' => $recordingId,
                    'artist_id' => $artistId,
                    'role' => 'performer',
                    'sort_order' => $sortOrder,
                ]);

                $sortOrder += 10;
            }
        }
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
     * @param array<int, array{artist_slugs:array<int, string>}> $songs
     * @return array<int, string>
     */
    private function findArtistSlugs(array $songs): array
    {
        $artistSlugs = [];

        foreach ($songs as $song) {
            foreach ($song['artist_slugs'] as $artistSlug) {
                $artistSlugs[$artistSlug] = $artistSlug;
            }
        }

        return array_values($artistSlugs);
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
    private function findLanguageIdsByCode(): array
    {
        $languageIdsByCode = (new Query())
            ->select(['id', 'code'])
            ->from('{{%language}}')
            ->andWhere(['code' => ['ka', 'ru', 'en', 'fr']])
            ->indexBy('code')
            ->column();

        if (
            isset(
                $languageIdsByCode['ka'],
                $languageIdsByCode['ru'],
                $languageIdsByCode['en'],
                $languageIdsByCode['fr'],
            )
        ) {
            return array_map(static fn ($value): int => (int) $value, $languageIdsByCode);
        }

        throw new RuntimeException('Required languages ka, ru, en and fr were not found.');
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, array{id:int, published_at:int|null}>
     */
    private function findExistingSongsBySlug(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'slug', 'published_at'])
            ->from('{{%song}}')
            ->andWhere(['slug' => $slugs]);

        $songs = [];

        foreach ($query->each() as $row) {
            $songs[(string) $row['slug']] = [
                'id' => (int) $row['id'],
                'published_at' => $row['published_at'] === null ? null : (int) $row['published_at'],
            ];
        }

        return $songs;
    }

    /**
     * @param array<int, int> $songIds
     * @param array<int, int> $languageIds
     * @return array<string, int>
     */
    private function findExistingSongTranslationsBySongAndLanguage(
        array $songIds,
        array $languageIds,
    ): array {
        if ($songIds === [] || $languageIds === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'song_id', 'language_id'])
            ->from('{{%song_translation}}')
            ->andWhere(['song_id' => $songIds])
            ->andWhere(['language_id' => $languageIds]);

        $translationIds = [];

        foreach ($query->each() as $row) {
            $translationIds[$this->createSongLanguageKey(
                (int) $row['song_id'],
                (int) $row['language_id'],
            )] = (int) $row['id'];
        }

        return $translationIds;
    }

    /**
     * @param array<int, array{
     *     slug:string,
     *     recordings:array<int, array{
     *         slug:string,
     *         recording_type:string,
     *         default_title:string,
     *         publication_status:string,
     *         artist_slugs:array<int, string>,
     *         legacy_path:string
     *     }>
     * }> $songs
     * @param array<string, int> $songIdsBySlug
     * @return array<int, array{
     *     song_id:int,
     *     slug:string,
     *     recording_type:string,
     *     default_title:string,
     *     publication_status:string,
     *     artist_slugs:array<int, string>,
     *     legacy_path:string
     * }>
     */
    private function findImportRecordings(array $songs, array $songIdsBySlug): array
    {
        $recordings = [];

        foreach ($songs as $song) {
            $songId = $songIdsBySlug[$song['slug']] ?? null;

            if ($songId === null) {
                continue;
            }

            foreach ($song['recordings'] as $recording) {
                $legacyPath = trim((string) ($recording['legacy_path'] ?? ''));

                if ($legacyPath === '') {
                    continue;
                }

                $recordings[] = [
                    'song_id' => $songId,
                    'slug' => trim((string) ($recording['slug'] ?? '')),
                    'recording_type' => trim((string) ($recording['recording_type'] ?? 'audio')),
                    'default_title' => trim((string) ($recording['default_title'] ?? $song['slug'])),
                    'publication_status' => trim((string) ($recording['publication_status'] ?? 'draft')),
                    'artist_slugs' => $recording['artist_slugs'] ?? [],
                    'legacy_path' => $legacyPath,
                ];
            }
        }

        return $recordings;
    }

    /**
     * @return array<int, string>
     */
    private function splitFullTextLines(string $text): array
    {
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_map(
            static fn (string $line): string => trim($line),
            explode("\n", $normalizedText),
        );

        return array_values(array_filter(
            $lines,
            static fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * @param array<int, string> $originalLines
     * @return array<int, string>
     */
    private function alignTranslatedLines(array $originalLines, string $translatedText): array
    {
        if ($originalLines === []) {
            return [];
        }

        $translatedLines = $this->splitFullTextLines($translatedText);

        if ($translatedLines === []) {
            return array_fill(0, count($originalLines), '');
        }

        if (count($translatedLines) === count($originalLines)) {
            return $translatedLines;
        }

        return $this->mapTranslatedLinesByRatio(
            $translatedLines,
            count($originalLines),
        );
    }

    /**
     * @param array<int, string> $translatedLines
     * @return array<int, string>
     */
    private function mapTranslatedLinesByRatio(array $translatedLines, int $targetCount): array
    {
        $mappedLines = array_fill(0, $targetCount, '');

        foreach ($translatedLines as $index => $translatedLine) {
            $targetIndex = min(
                $targetCount - 1,
                (int) floor($index * $targetCount / count($translatedLines)),
            );

            $mappedLines[$targetIndex] = $mappedLines[$targetIndex] === ''
                ? $translatedLine
                : trim($mappedLines[$targetIndex] . ' ' . $translatedLine);
        }

        return $mappedLines;
    }

    /**
     * @param array<int, array{slug:string}> $recordings
     * @return array<int, string>
     */
    private function findRecordingSlugs(array $recordings): array
    {
        return array_column($recordings, 'slug');
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, array{id:int, published_at:int|null}>
     */
    private function findExistingRecordingsBySlug(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $query = (new Query())
            ->select(['id', 'slug', 'published_at'])
            ->from('{{%recording}}')
            ->andWhere(['slug' => $slugs]);

        $recordings = [];

        foreach ($query->each() as $row) {
            $recordings[(string) $row['slug']] = [
                'id' => (int) $row['id'],
                'published_at' => $row['published_at'] === null ? null : (int) $row['published_at'],
            ];
        }

        return $recordings;
    }

    private function createSongLanguageKey(int $songId, int $languageId): string
    {
        return $songId . ':' . $languageId;
    }

    private function createPublishedAtValue(
        string $publicationStatus,
        int | null $existingPublishedAt,
        int $timestamp,
    ): int | null {
        if ($publicationStatus !== 'published') {
            return $existingPublishedAt;
        }

        if ($existingPublishedAt !== null) {
            return $existingPublishedAt;
        }

        return $timestamp;
    }

    /**
     * @param array{created_at:int|string|null} $song
     */
    private function findSongCreatedAt(array $song, int $fallbackTimestamp): int
    {
        $createdAt = (int) ($song['created_at'] ?? 0);

        if ($createdAt > 0) {
            return $createdAt;
        }

        return $fallbackTimestamp;
    }

    private function createLegacyMediaDescription(string $recordingType, string $legacyPath): string
    {
        if ($recordingType === 'video') {
            return 'Legacy video path: ' . $legacyPath;
        }

        return 'Legacy audio path: ' . $legacyPath;
    }

    private function normalizeNullableText(mixed $value): string | null
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return $text;
    }

    private function getCurrentTimestamp(): int
    {
        return time();
    }

    private function getDataFilePath(): string
    {
        return __DIR__ . '/data/legacySongs.json';
    }

    private function findLastInsertId(): int
    {
        return (int) $this->db->getLastInsertID();
    }

    private function getChordSheetParser(): ChordSheetParser
    {
        if ($this->chordSheetParser !== null) {
            return $this->chordSheetParser;
        }

        $this->chordSheetParser = new ChordSheetParser();

        return $this->chordSheetParser;
    }
}
