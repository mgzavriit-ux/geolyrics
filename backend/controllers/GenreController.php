<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\GenreSearch;
use common\models\Genre;
use common\models\GenreTranslation;
use common\models\Language;
use Yii;
use yii\base\Model;
use yii\db\IntegrityException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class GenreController extends AdminController
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
        $model = new Genre();
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadGenreForm($model, $translationModels) && $this->validateGenreForm($model, $translationModels)) {
            $this->saveGenreForm($model, $translationModels);
            Yii::$app->session->setFlash('success', 'Жанр сохранен.');

            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'translationModels' => $translationModels,
            'languageLabels' => $this->findLanguageLabels($languages),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Жанр удален.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash(
                'warning',
                'Жанр нельзя удалить, пока он связан с песнями. Сначала отвяжите его от песен.',
            );
        }

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new GenreSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $genre = new Genre();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'publicationStatusItems' => $genre->getPublicationStatusList(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadGenreForm($model, $translationModels) && $this->validateGenreForm($model, $translationModels)) {
            $this->saveGenreForm($model, $translationModels);
            Yii::$app->session->setFlash('success', 'Жанр обновлен.');

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'translationModels' => $translationModels,
            'languageLabels' => $this->findLanguageLabels($languages),
        ]);
    }

    private function findModel(int $id): Genre
    {
        $model = Genre::find()
            ->with(['translations'])
            ->andWhere(['id' => $id])
            ->one();

        if ($model instanceof Genre) {
            return $model;
        }

        throw new NotFoundHttpException('Жанр не найден.');
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
     * @param Language[] $languages
     *
     * @return GenreTranslation[]
     */
    private function findTranslationModels(Genre $genre, array $languages): array
    {
        $translationByLanguageId = [];

        if ($genre->isNewRecord === false) {
            $translationByLanguageId = GenreTranslation::find()
                ->indexBy('language_id')
                ->andWhere(['genre_id' => $genre->id])
                ->all();
        }

        $translationModels = [];

        foreach ($languages as $language) {
            $translationModel = $translationByLanguageId[$language->id] ?? new GenreTranslation();
            $translationModel->language_id = $language->id;

            if ($genre->isNewRecord === false) {
                $translationModel->genre_id = $genre->id;
            }

            $translationModels[] = $translationModel;
        }

        return $translationModels;
    }

    /**
     * @param GenreTranslation[] $translationModels
     */
    private function loadGenreForm(Genre $model, array $translationModels): bool
    {
        if (Yii::$app->request->isPost === false) {
            return false;
        }

        $model->load(Yii::$app->request->post());
        Model::loadMultiple($translationModels, Yii::$app->request->post());

        return true;
    }

    /**
     * @param GenreTranslation[] $translationModels
     */
    private function validateGenreForm(Genre $model, array $translationModels): bool
    {
        $isValid = $model->validate();

        return Model::validateMultiple($translationModels) && $isValid;
    }

    /**
     * @param GenreTranslation[] $translationModels
     */
    private function saveGenreForm(Genre $model, array $translationModels): void
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
     * @param GenreTranslation[] $translationModels
     */
    private function saveTranslationModels(Genre $genre, array $translationModels): void
    {
        foreach ($translationModels as $translationModel) {
            if ($translationModel->hasContent() === false) {
                if ($translationModel->isNewRecord === false) {
                    $translationModel->delete();
                }

                continue;
            }

            $translationModel->genre_id = $genre->id;
            $translationModel->save(false);
        }
    }
}
