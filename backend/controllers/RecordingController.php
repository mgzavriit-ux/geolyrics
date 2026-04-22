<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\RecordingSearch;
use common\models\Recording;
use common\models\Song;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class RecordingController extends AdminController
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
        $model = new Recording();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Запись сохранена.');

            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'recordingTypeItems' => $model->getRecordingTypeList(),
            'songItems' => $this->findSongItems(),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Запись удалена.');

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new RecordingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $recording = new Recording();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'publicationStatusItems' => $recording->getPublicationStatusList(),
            'recordingTypeItems' => $recording->getRecordingTypeList(),
            'songItems' => $this->findSongItems(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Запись обновлена.');

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'recordingTypeItems' => $model->getRecordingTypeList(),
            'songItems' => $this->findSongItems(),
        ]);
    }

    private function findModel(int $id): Recording
    {
        $model = Recording::find()->with(['song'])->andWhere(['id' => $id])->one();

        if ($model instanceof Recording) {
            return $model;
        }

        throw new NotFoundHttpException('Запись не найдена.');
    }

    private function findSongItems(): array
    {
        return Song::find()
            ->orderBy(['default_title' => SORT_ASC, 'id' => SORT_ASC])
            ->select(['default_title', 'id'])
            ->indexBy('id')
            ->column();
    }
}
