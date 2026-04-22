<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\SongSearch;
use common\models\Language;
use common\models\Song;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class SongController extends AdminController
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
        $model = new Song();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Песня сохранена.');

            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'languageItems' => $this->findLanguageItems(),
            'publicationStatusItems' => $model->getPublicationStatusList(),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Песня удалена.');

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new SongSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $song = new Song();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'languageItems' => $this->findLanguageItems(),
            'publicationStatusItems' => $song->getPublicationStatusList(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Песня обновлена.');

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'languageItems' => $this->findLanguageItems(),
            'publicationStatusItems' => $model->getPublicationStatusList(),
        ]);
    }

    private function findLanguageItems(): array
    {
        return Language::find()
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }

    private function findModel(int $id): Song
    {
        $model = Song::find()->with(['originalLanguage'])->andWhere(['id' => $id])->one();

        if ($model instanceof Song) {
            return $model;
        }

        throw new NotFoundHttpException('Песня не найдена.');
    }
}
