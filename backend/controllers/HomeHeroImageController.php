<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\HomeHeroImageForm;
use backend\models\HomeHeroImageSearch;
use common\app\WebApplication;
use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\HomeHeroImage;
use common\models\Language;
use common\services\HomeHeroImageManager;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class HomeHeroImageController extends AdminController
{
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ]);
    }

    public function actionCreate(): string|Response
    {
        return $this->handleForm(new HomeHeroImage(), 'create', 'Hero-изображение сохранено.');
    }

    public function actionDelete(int $id): Response
    {
        $this->getImageManager()->deleteImage($this->findModel($id));
        Yii::$app->session->setFlash('success', 'Hero-изображение удалено.');

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new HomeHeroImageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'artistItems' => $this->findArtistItems(),
            'storage' => $this->getStorage(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        return $this->handleForm($this->findModel($id), 'update', 'Hero-изображение обновлено.');
    }

    private function findArtistItems(): array
    {
        $items = [];
        $russianLanguageId = $this->findLanguageIdByCode('ru');

        foreach ($this->findArtists() as $artist) {
            $items[$artist->id] = $russianLanguageId === null
                ? $artist->default_name
                : $artist->getNameByLanguageId($russianLanguageId);
        }

        asort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }

    /**
     * @return Artist[]
     */
    private function findArtists(): array
    {
        return Artist::find()
            ->with(['translations'])
            ->orderBy(['default_name' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    private function findLanguageIdByCode(string $code): int | null
    {
        $languageId = Language::find()
            ->select(['id'])
            ->andWhere(['code' => $code])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            return null;
        }

        return (int) $languageId;
    }

    private function findModel(int $id): HomeHeroImage
    {
        $model = HomeHeroImage::find()
            ->with(['artist', 'mediaAsset'])
            ->andWhere(['id' => $id])
            ->one();

        if ($model instanceof HomeHeroImage) {
            return $model;
        }

        throw new NotFoundHttpException('Hero-изображение не найдено.');
    }

    private function getImageManager(): HomeHeroImageManager
    {
        return new HomeHeroImageManager($this->getStorage());
    }

    private function getStorage(): StorageInterface
    {
        /** @var WebApplication $app */
        $app = Yii::$app;

        return $app->storage;
    }

    private function handleForm(HomeHeroImage $model, string $view, string $successMessage): string|Response
    {
        $formModel = new HomeHeroImageForm($model, $this->getStorage());

        if ($formModel->load(Yii::$app->request->post()) && $formModel->validate()) {
            $this->saveForm($formModel);
            Yii::$app->session->setFlash('success', $successMessage);

            return $this->redirect(['index']);
        }

        return $this->render($view, [
            'formModel' => $formModel,
            'artistItems' => $this->findArtistItems(),
        ]);
    }

    private function saveForm(HomeHeroImageForm $formModel): void
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $formModel->save();
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }
}
