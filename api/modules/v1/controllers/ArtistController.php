<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\modules\v1\presenters\ArtistPresenter;
use common\app\WebApplication;
use common\components\storage\StorageInterface;
use common\models\Artist;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;
use yii\web\Request;

final class ArtistController extends JsonRestController
{
    public function actionIndex(): array
    {
        $limit = $this->findLimit();
        $offset = $this->findOffset();
        $query = $this->findPublishedArtistsQuery();
        $total = (int) (clone $query)->count();
        $artists = $query
            ->with([
                'artistImages.mediaAsset',
                'translations.language',
            ])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        return [
            'items' => array_map(
                fn (Artist $artist): array => $this->getArtistPresenter()->presentListItem($artist),
                $artists,
            ),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(string $slug): array
    {
        $artist = $this->findPublishedArtistBySlug($slug);

        return $this->getArtistPresenter()->presentDetail($artist);
    }

    protected function findPublishedArtistBySlug(string $slug): Artist
    {
        $artist = $this->findPublishedArtistsQuery()
            ->with([
                'artistImages.mediaAsset',
                'translations.language',
                'recordings.song.translations.language',
            ])
            ->andWhere(['slug' => $slug])
            ->one();

        if ($artist instanceof Artist) {
            return $artist;
        }

        throw new NotFoundHttpException('Artist not found.');
    }

    protected function findPublishedArtistsQuery(): ActiveQuery
    {
        return Artist::find()
            ->andWhere(['publication_status' => Artist::PUBLICATION_STATUS_PUBLISHED]);
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

    protected function getArtistPresenter(): ArtistPresenter
    {
        return new ArtistPresenter($this->getStorage());
    }

    protected function getRequest(): Request
    {
        /** @var Request $request */
        $request = \Yii::$app->request;

        return $request;
    }

    protected function getStorage(): StorageInterface
    {
        /** @var WebApplication $app */
        $app = \Yii::$app;

        return $app->storage;
    }
}
