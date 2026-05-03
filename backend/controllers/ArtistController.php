<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\ArtistSearch;
use common\models\Artist;
use common\models\ArtistTranslation;
use common\models\Language;
use Yii;
use yii\base\Model;
use yii\db\IntegrityException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class ArtistController extends AdminController
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
        $model = new Artist();
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadArtistForm($model, $translationModels) && $this->validateArtistForm($model, $translationModels)) {
            $this->saveArtistForm($model, $translationModels);

            Yii::$app->session->setFlash('success', 'Исполнитель сохранен.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'typeItems' => $model->getTypeList(),
            'translationModels' => $translationModels,
            'languageLabels' => $this->findLanguageLabels($languages),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Исполнитель удален.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash(
                'warning',
                'Исполнителя нельзя удалить, пока он связан с песнями или записями. Сначала удалите или отвяжите связанные сущности.',
            );
        }

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new ArtistSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $artist = new Artist();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'publicationStatusItems' => $artist->getPublicationStatusList(),
            'typeItems' => $artist->getTypeList(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadArtistForm($model, $translationModels) && $this->validateArtistForm($model, $translationModels)) {
            $this->saveArtistForm($model, $translationModels);

            Yii::$app->session->setFlash('success', 'Исполнитель обновлен.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'typeItems' => $model->getTypeList(),
            'translationModels' => $translationModels,
            'languageLabels' => $this->findLanguageLabels($languages),
        ]);
    }

    private function findModel(int $id): Artist
    {
        $model = Artist::find()->with(['translations'])->andWhere(['id' => $id])->one();

        if ($model instanceof Artist) {
            return $model;
        }

        throw new NotFoundHttpException('Исполнитель не найден.');
    }

    /**
     * @param Language[] $languages
     *
     * @return ArtistTranslation[]
     */
    private function findTranslationModels(Artist $artist, array $languages): array
    {
        $translationByLanguageId = [];

        if ($artist->isNewRecord === false) {
            $translationByLanguageId = ArtistTranslation::find()
                ->indexBy('language_id')
                ->andWhere(['artist_id' => $artist->id])
                ->all();
        }

        $translationModels = [];

        foreach ($languages as $language) {
            $translationModel = $translationByLanguageId[$language->id] ?? new ArtistTranslation();
            $translationModel->language_id = $language->id;

            if ($artist->isNewRecord === false) {
                $translationModel->artist_id = $artist->id;
            }

            $translationModels[] = $translationModel;
        }

        return $translationModels;
    }

    /**
     * @return Language[]
     */
    private function findLanguages(): array
    {
        return Language::find()
            ->andWhere(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /**
     * @param Language[] $languages
     */
    private function findLanguageLabels(array $languages): array
    {
        $items = [];

        foreach ($languages as $language) {
            $items[$language->id] = $language->native_name . ' (' . $language->code . ')';
        }

        return $items;
    }

    /**
     * @param ArtistTranslation[] $translationModels
     */
    private function loadArtistForm(Artist $model, array $translationModels): bool
    {
        if (Yii::$app->request->isPost === false) {
            return false;
        }

        $model->load(Yii::$app->request->post());
        Model::loadMultiple($translationModels, Yii::$app->request->post());

        return true;
    }

    /**
     * @param ArtistTranslation[] $translationModels
     */
    private function saveArtistForm(Artist $model, array $translationModels): void
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $model->save(false);
            $this->saveTranslationModels($model, $translationModels);
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    /**
     * @param ArtistTranslation[] $translationModels
     */
    private function saveTranslationModels(Artist $artist, array $translationModels): void
    {
        foreach ($translationModels as $translationModel) {
            if ($translationModel->hasContent() === false) {
                if ($translationModel->isNewRecord === false) {
                    $translationModel->delete();
                }

                continue;
            }

            $translationModel->artist_id = $artist->id;
            $translationModel->save(false);
        }
    }

    /**
     * @param ArtistTranslation[] $translationModels
     */
    private function validateArtistForm(Artist $model, array $translationModels): bool
    {
        $isValid = $model->validate();

        return Model::validateMultiple($translationModels) && $isValid;
    }
}
