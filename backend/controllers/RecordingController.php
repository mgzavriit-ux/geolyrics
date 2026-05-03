<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\RecordingMediaUploadForm;
use backend\models\RecordingSearch;
use common\components\storage\StorageInterface;
use common\app\WebApplication;
use common\models\Recording;
use common\models\Song;
use common\services\CatalogSlugGenerator;
use common\services\RecordingRemover;
use common\services\RecordingMediaUploader;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
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
        $uploadForm = new RecordingMediaUploadForm();

        if ($model->load(Yii::$app->request->post())) {
            $this->loadUploadFormFiles($uploadForm);
            $this->prepareRecordingSlug($model);

            if ($model->validate() && $uploadForm->validate()) {
                $this->saveRecordingForm($model, $uploadForm);
                Yii::$app->session->setFlash('success', 'Запись сохранена.');

                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'uploadForm' => $uploadForm,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'recordingTypeItems' => $model->getRecordingTypeList(),
            'songItems' => $this->findSongItems(),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->getRecordingRemover()->deleteRecording($this->findModel($id));
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
        $uploadForm = new RecordingMediaUploadForm();

        if ($model->load(Yii::$app->request->post())) {
            $this->loadUploadFormFiles($uploadForm);
            $this->prepareRecordingSlug($model);

            if ($model->validate() && $uploadForm->validate()) {
                $this->saveRecordingForm($model, $uploadForm);
                Yii::$app->session->setFlash('success', 'Запись обновлена.');

                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'uploadForm' => $uploadForm,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'recordingTypeItems' => $model->getRecordingTypeList(),
            'songItems' => $this->findSongItems(),
        ]);
    }

    private function findModel(int $id): Recording
    {
        $model = Recording::find()
            ->with(['song', 'coverMediaAsset', 'recordingMediaEntries.mediaAsset'])
            ->andWhere(['id' => $id])
            ->one();

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

    private function prepareRecordingSlug(Recording $recording): void
    {
        if ($recording->song_id === null) {
            return;
        }

        $song = Song::findOne($recording->song_id);

        if ($song instanceof Song === false) {
            return;
        }

        $recordingSlug = (new CatalogSlugGenerator())->generateRecordingSlug($recording, $song);

        if ($recordingSlug === '') {
            if (trim((string) $recording->recording_type) !== '' && trim((string) $song->slug) !== '') {
                $recording->addError('recording_type', 'Не удалось сформировать slug записи. Проверь таблицу транслитерации.');
            }

            return;
        }

        $recording->slug = $recordingSlug;
    }

    private function loadUploadFormFiles(RecordingMediaUploadForm $uploadForm): void
    {
        $uploadForm->audioFile = UploadedFile::getInstance($uploadForm, 'audioFile');
        $uploadForm->coverFile = UploadedFile::getInstance($uploadForm, 'coverFile');
        $uploadForm->videoFile = UploadedFile::getInstance($uploadForm, 'videoFile');
    }

    private function saveRecordingForm(Recording $recording, RecordingMediaUploadForm $uploadForm): void
    {
        $transaction = $this->getDb()->beginTransaction();

        try {
            $recording->save(false);
            $this->saveRecordingMediaFiles($recording, $uploadForm);
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    private function saveRecordingMediaFiles(Recording $recording, RecordingMediaUploadForm $uploadForm): void
    {
        $uploader = new RecordingMediaUploader($this->getStorage());

        if ($uploadForm->audioFile instanceof UploadedFile) {
            $uploader->uploadAudioFile($recording, $uploadForm->audioFile);
        }

        if ($uploadForm->coverFile instanceof UploadedFile) {
            $uploader->uploadCoverFile($recording, $uploadForm->coverFile);
        }

        if ($uploadForm->videoFile instanceof UploadedFile) {
            $uploader->uploadVideoFile($recording, $uploadForm->videoFile);
        }
    }

    private function getDb(): \yii\db\Connection
    {
        return Yii::$app->db;
    }

    private function getRecordingRemover(): RecordingRemover
    {
        return new RecordingRemover($this->getStorage());
    }

    private function getStorage(): StorageInterface
    {
        /** @var WebApplication $app */
        $app = Yii::$app;

        return $app->storage;
    }
}
