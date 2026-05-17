<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\modules\v1\presenters\SongPresenter;
use common\app\WebApplication;
use common\components\storage\StorageInterface;
use common\models\Song;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;
use yii\web\Request;

final class SongController extends JsonRestController
{
    public function actionIndex(): array
    {
        $limit = $this->findLimit();
        $offset = $this->findOffset();
        $query = $this->findPublishedSongsQuery();
        $total = (int) (clone $query)->count();
        $songs = $query
            ->with([
                'coverMediaAsset',
                'originalLanguage',
                'translations.language',
            ])
            ->orderBy(['id' => SORT_ASC])
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

    protected function findPublishedSongBySlug(string $slug): Song
    {
        $song = $this->findPublishedSongsQuery()
            ->with([
                'coverMediaAsset',
                'originalLanguage',
                'translations.language',
                'songLines.translations.language',
                'songArrangements',
                'recordings.coverMediaAsset',
                'recordings.recordingMediaEntries.mediaAsset',
                'recordings.recordingArtists.artist.translations.language',
            ])
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
            ->andWhere(['publication_status' => Song::PUBLICATION_STATUS_PUBLISHED]);
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
        return new SongPresenter($this->getStorage());
    }

    protected function getStorage(): StorageInterface
    {
        /** @var WebApplication $app */
        $app = \Yii::$app;

        return $app->storage;
    }
}
