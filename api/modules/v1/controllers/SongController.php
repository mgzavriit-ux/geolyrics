<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\modules\v1\presenters\SongPresenter;
use common\app\WebApplication;
use common\components\storage\StorageInterface;
use common\models\Recording;
use common\models\RecordingMedia;
use common\models\Song;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\web\NotFoundHttpException;
use yii\web\Request;

final class SongController extends JsonRestController
{
    private const string SORT_OLDEST = 'oldest';
    private const string SORT_TITLE = 'title';

    private bool | null $hasSongTitleTransliterationTable = null;

    public function actionIndex(): array
    {
        $limit = $this->findLimit();
        $offset = $this->findOffset();
        $query = $this->findPublishedSongsQuery();
        $this->applyRequestFilters($query);
        $total = (int) (clone $query)->count();
        $songs = $query
            ->with($this->findListRelations())
            ->orderBy($this->findSongsOrderBy())
            ->limit($limit)
            ->offset($offset)
            ->all();

        return [
            'items' => array_map(
                fn (Song $song): array => $this->getSongPresenter()->presentListItem($song),
                $songs,
            ),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    public function actionView(string $slug): array
    {
        $song = $this->findPublishedSongBySlug($slug);

        return $this->getSongPresenter()->presentDetail($song);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function findPublishedSongBySlug(string $slug): Song
    {
        $song = $this->findPublishedSongsQuery()
            ->with($this->findDetailRelations())
            ->andWhere(['slug' => $slug])
            ->one();

        if ($song instanceof Song) {
            return $song;
        }

        throw new NotFoundHttpException('Song not found.');
    }

    protected function findPublishedSongsQuery(): ActiveQuery
    {
        return Song::find()
            ->alias('s')
            ->andWhere(['s.publication_status' => Song::PUBLICATION_STATUS_PUBLISHED]);
    }

    protected function applyRequestFilters(ActiveQuery $query): void
    {
        $this->applySearchFilter($query);
        $this->applyArtistFilter($query);
        $this->applyTagFilter($query);
        $this->applyHasAudioFilter($query);
        $this->applyHasChordsFilter($query);
        $this->applyHasTranslationFilter($query);
        $this->applyHasTranscriptionFilter($query);
    }

    protected function applySearchFilter(ActiveQuery $query): void
    {
        $searchValue = $this->findStringRequestParam('q');

        if ($searchValue === '') {
            return;
        }

        $conditions = [
            'or',
            ['like', 's.default_title', $searchValue],
            ['like', 's.slug', $searchValue],
            ['exists', $this->createSongTranslationSearchQuery($searchValue)],
            ['exists', $this->createSongLineSearchQuery($searchValue)],
            ['exists', $this->createSongLineTranslationSearchQuery($searchValue)],
            ['exists', $this->createRecordingSearchQuery($searchValue)],
            ['exists', $this->createArtistSearchQuery($searchValue)],
            ['exists', $this->createTagSearchQuery($searchValue)],
        ];

        if ($this->hasSongTitleTransliterationTable()) {
            $conditions[] = ['exists', $this->createSongTitleTransliterationSearchQuery($searchValue)];
        }

        $query->andWhere($conditions);
    }

    protected function applyArtistFilter(ActiveQuery $query): void
    {
        $artist = $this->findStringRequestParam('artist');

        if ($artist === '') {
            return;
        }

        $artistQuery = $this->createPublishedRecordingArtistQuery();

        if (ctype_digit($artist)) {
            $artistQuery->andWhere(['a.id' => (int) $artist]);
        } else {
            $artistQuery->andWhere(['a.slug' => $artist]);
        }

        $query->andWhere(['exists', $artistQuery]);
    }

    protected function applyTagFilter(ActiveQuery $query): void
    {
        $tag = $this->findStringRequestParam('tag');

        if ($tag === '') {
            return;
        }

        $query->andWhere([
            'exists',
            (new Query())
                ->select(new Expression('1'))
                ->from(['st' => '{{%song_tag}}'])
                ->innerJoin(['t' => '{{%tag}}'], 't.id = st.tag_id')
                ->where('st.song_id = s.id')
                ->andWhere(['t.slug' => $tag]),
        ]);
    }

    protected function applyHasAudioFilter(ActiveQuery $query): void
    {
        if ($this->findBooleanRequestParam('hasAudio') === false) {
            return;
        }

        $query->andWhere(['exists', $this->createRecordingMediaQuery(RecordingMedia::ROLE_AUDIO)]);
    }

    protected function applyHasChordsFilter(ActiveQuery $query): void
    {
        if ($this->findBooleanRequestParam('hasChords') === false) {
            return;
        }

        $query->andWhere([
            'exists',
            (new Query())
                ->select(new Expression('1'))
                ->from(['sa' => '{{%song_arrangement}}'])
                ->where('sa.song_id = s.id'),
        ]);
    }

    protected function applyHasTranslationFilter(ActiveQuery $query): void
    {
        if ($this->findBooleanRequestParam('hasTranslation') === false) {
            return;
        }

        $query->andWhere([
            'or',
            ['exists', $this->createSongTranslationContentQuery()],
            ['exists', $this->createSongLineTranslationContentQuery()],
        ]);
    }

    protected function applyHasTranscriptionFilter(ActiveQuery $query): void
    {
        if ($this->findBooleanRequestParam('hasTranscription') === false) {
            return;
        }

        if ($this->hasSongTitleTransliterationTable() === false) {
            $query->andWhere(new Expression('1 = 0'));

            return;
        }

        $query->andWhere([
            'exists',
            (new Query())
                ->select(new Expression('1'))
                ->from(['stt' => '{{%song_title_transliteration}}'])
                ->where('stt.song_id = s.id'),
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function findSongsOrderBy(): array
    {
        $sort = $this->findStringRequestParam('sort');

        if ($sort === self::SORT_TITLE) {
            return [
                's.default_title' => SORT_ASC,
                's.id' => SORT_ASC,
            ];
        }

        if ($sort === self::SORT_OLDEST) {
            return [
                's.published_at' => SORT_ASC,
                's.id' => SORT_ASC,
            ];
        }

        return [
            's.published_at' => SORT_DESC,
            's.id' => SORT_DESC,
        ];
    }

    protected function findLimit(): int
    {
        $limit = (int) $this->getRequest()->get('limit', 20);

        if ($limit < 1) {
            return 20;
        }

        if ($limit > 100) {
            return 100;
        }

        return $limit;
    }

    protected function findOffset(): int
    {
        $offset = (int) $this->getRequest()->get('offset', 0);

        if ($offset < 0) {
            return 0;
        }

        return $offset;
    }

    protected function getRequest(): Request
    {
        /** @var Request $request */
        $request = \Yii::$app->request;

        return $request;
    }

    protected function getSongPresenter(): SongPresenter
    {
        return new SongPresenter(
            $this->getStorage(),
            $this->hasSongTitleTransliterationTable(),
        );
    }

    protected function getStorage(): StorageInterface
    {
        /** @var WebApplication $app */
        $app = \Yii::$app;

        return $app->storage;
    }

    /**
     * @return string[]
     */
    private function findListRelations(): array
    {
        $relations = [
            'coverMediaAsset',
            'originalLanguage',
            'recordings.coverMediaAsset',
            'recordings.recordingMediaEntries.mediaAsset',
            'recordings.recordingArtists.artist.artistImages.mediaAsset',
            'recordings.recordingArtists.artist.translations.language',
            'songArrangements',
            'songLines.translations.language',
            'tags',
            'translations.language',
        ];

        if ($this->hasSongTitleTransliterationTable()) {
            $relations[] = 'titleTransliterations';
        }

        return $relations;
    }

    /**
     * @return string[]
     */
    private function findDetailRelations(): array
    {
        $relations = [
            'coverMediaAsset',
            'originalLanguage',
            'recordings.recordingArtists.artist.artistImages.mediaAsset',
            'translations.language',
            'songLines.translations.language',
            'songArrangements',
            'recordings.coverMediaAsset',
            'recordings.recordingMediaEntries.mediaAsset',
            'recordings.recordingArtists.artist.translations.language',
        ];

        if ($this->hasSongTitleTransliterationTable()) {
            $relations[] = 'titleTransliterations';
        }

        return $relations;
    }

    private function hasSongTitleTransliterationTable(): bool
    {
        if ($this->hasSongTitleTransliterationTable !== null) {
            return $this->hasSongTitleTransliterationTable;
        }

        $this->hasSongTitleTransliterationTable = $this->getDb()
            ->schema
            ->getTableSchema('{{%song_title_transliteration}}', true) !== null;

        return $this->hasSongTitleTransliterationTable;
    }

    private function getDb(): Connection
    {
        /** @var Connection $db */
        $db = \Yii::$app->db;

        return $db;
    }

    private function findStringRequestParam(string $name): string
    {
        return trim((string) $this->getRequest()->get($name, ''));
    }

    private function findBooleanRequestParam(string $name): bool
    {
        $value = $this->findStringRequestParam($name);

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function createSongTranslationSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['st' => '{{%song_translation}}'])
            ->where('st.song_id = s.id')
            ->andWhere([
                'or',
                ['like', 'st.title', $searchValue],
                ['like', 'st.subtitle', $searchValue],
                ['like', 'st.description', $searchValue],
                ['like', 'st.history', $searchValue],
            ]);
    }

    private function createSongLineSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['sl' => '{{%song_line}}'])
            ->where('sl.song_id = s.id')
            ->andWhere(['like', 'sl.original_text', $searchValue]);
    }

    private function createSongLineTranslationSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['slt' => '{{%song_line_translation}}'])
            ->innerJoin(['sl' => '{{%song_line}}'], 'sl.id = slt.song_line_id')
            ->where('sl.song_id = s.id')
            ->andWhere(['like', 'slt.translated_text', $searchValue]);
    }

    private function createSongTitleTransliterationSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['stt' => '{{%song_title_transliteration}}'])
            ->where('stt.song_id = s.id')
            ->andWhere(['like', 'stt.transliterated_text', $searchValue]);
    }

    private function createRecordingSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['r' => '{{%recording}}'])
            ->where('r.song_id = s.id')
            ->andWhere(['r.publication_status' => Recording::PUBLICATION_STATUS_PUBLISHED])
            ->andWhere([
                'or',
                ['like', 'r.default_title', $searchValue],
                ['like', 'r.slug', $searchValue],
                ['like', 'r.description', $searchValue],
            ]);
    }

    private function createArtistSearchQuery(string $searchValue): Query
    {
        return $this->createPublishedRecordingArtistQuery()
            ->leftJoin(['at' => '{{%artist_translation}}'], 'at.artist_id = a.id')
            ->andWhere([
                'or',
                ['like', 'a.default_name', $searchValue],
                ['like', 'a.slug', $searchValue],
                ['like', 'at.name', $searchValue],
                ['like', 'at.biography', $searchValue],
            ]);
    }

    private function createTagSearchQuery(string $searchValue): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['st' => '{{%song_tag}}'])
            ->innerJoin(['t' => '{{%tag}}'], 't.id = st.tag_id')
            ->where('st.song_id = s.id')
            ->andWhere([
                'or',
                ['like', 't.default_name', $searchValue],
                ['like', 't.slug', $searchValue],
            ]);
    }

    private function createPublishedRecordingArtistQuery(): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['ra' => '{{%recording_artist}}'])
            ->innerJoin(['r' => '{{%recording}}'], 'r.id = ra.recording_id')
            ->innerJoin(['a' => '{{%artist}}'], 'a.id = ra.artist_id')
            ->where('r.song_id = s.id')
            ->andWhere(['r.publication_status' => Recording::PUBLICATION_STATUS_PUBLISHED]);
    }

    private function createRecordingMediaQuery(string $role): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['rm' => '{{%recording_media}}'])
            ->innerJoin(['r' => '{{%recording}}'], 'r.id = rm.recording_id')
            ->where('r.song_id = s.id')
            ->andWhere(['r.publication_status' => Recording::PUBLICATION_STATUS_PUBLISHED])
            ->andWhere(['rm.role' => $role]);
    }

    private function createSongTranslationContentQuery(): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['st' => '{{%song_translation}}'])
            ->where('st.song_id = s.id')
            ->andWhere([
                'or',
                ['<>', 'st.title', ''],
                ['<>', 'st.subtitle', ''],
                ['<>', 'st.description', ''],
                ['<>', 'st.history', ''],
            ]);
    }

    private function createSongLineTranslationContentQuery(): Query
    {
        return (new Query())
            ->select(new Expression('1'))
            ->from(['slt' => '{{%song_line_translation}}'])
            ->innerJoin(['sl' => '{{%song_line}}'], 'sl.id = slt.song_line_id')
            ->where('sl.song_id = s.id')
            ->andWhere(['<>', 'slt.translated_text', '']);
    }
}
