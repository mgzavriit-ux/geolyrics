<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\modules\v1\presenters\HomeHeroImagePresenter;
use common\app\WebApplication;
use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\HomeHeroImage;
use yii\web\Request;

final class HomeController extends JsonRestController
{
    public function actionHeroImages(): array
    {
        $limit = $this->findLimit();
        $heroImages = $this->findHeroImages($limit);

        return [
            'items' => array_map(
                fn (HomeHeroImage $heroImage): array => $this->getHomeHeroImagePresenter()->presentListItem($heroImage),
                $heroImages,
            ),
        ];
    }

    /**
     * @return HomeHeroImage[]
     */
    protected function findHeroImages(int $limit): array
    {
        $publishedArtistIdsQuery = Artist::find()
            ->select(['id'])
            ->andWhere(['publication_status' => Artist::PUBLICATION_STATUS_PUBLISHED]);

        return HomeHeroImage::find()
            ->andWhere(['is_active' => true])
            ->andWhere(['artist_id' => $publishedArtistIdsQuery])
            ->with([
                'artist.translations.language',
                'mediaAsset',
            ])
            ->orderBy([
                'sort_order' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->limit($limit)
            ->all();
    }

    protected function findLimit(): int
    {
        $limit = (int) $this->getRequest()->get('limit', 10);

        if ($limit < 1) {
            return 10;
        }

        if ($limit > 50) {
            return 50;
        }

        return $limit;
    }

    protected function getHomeHeroImagePresenter(): HomeHeroImagePresenter
    {
        return new HomeHeroImagePresenter($this->getStorage());
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
