<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\SongEditorForm;
use common\models\Artist;
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
                    'translation-fields' => ['get'],
                    'text-fields' => ['get'],
                ],
            ],
        ]);
    }

    public function actionCreate(): string|Response
    {
        return $this->handleForm(new Song(), 'create', 'Песня сохранена.');
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

    public function actionTranslationFields(int $languageId, int | null $id = null): string
    {
        $song = $id === null ? new Song() : $this->findModel($id);
        $formModel = $this->createFormModel($song);
        $translationModel = $formModel->getSongTranslationModelByLanguageId($languageId);
        $translationIndex = $formModel->getSongTranslationInputIndex($languageId);
        $languageLabel = $formModel->getSongTranslationLanguageItems()[$languageId] ?? null;

        if ($translationModel === null || $translationIndex === null || $languageLabel === null) {
            throw new NotFoundHttpException('Перевод для выбранного языка не найден.');
        }

        return $this->renderPartial('_translation_fields', [
            'languageId' => $languageId,
            'languageLabel' => $languageLabel,
            'isVisible' => true,
            'translationIndex' => $translationIndex,
            'translationModel' => $translationModel,
        ]);
    }

    public function actionTextFields(int $languageId, int | null $id = null): string
    {
        $song = $id === null ? new Song() : $this->findModel($id);
        $formModel = $this->createFormModel($song);
        $languageLabel = $formModel->getSongTextLanguageItems()[$languageId] ?? null;

        if ($languageLabel === null) {
            throw new NotFoundHttpException('Текст для выбранного языка не найден.');
        }

        return $this->renderPartial('_song_text_fields', [
            'languageId' => $languageId,
            'languageLabel' => $languageLabel,
            'isVisible' => true,
            'textValue' => $formModel->getSongTextValueByLanguageId($languageId),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        return $this->handleForm($this->findModel($id), 'update', 'Песня обновлена.');
    }

    private function findLanguageItems(): array
    {
        return Language::find()
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }

    /**
     * @return Artist[]
     */
    private function findArtists(): array
    {
        return Artist::find()
            ->orderBy(['default_name' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
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

    private function findModel(int $id): Song
    {
        $model = Song::find()->with(['originalLanguage'])->andWhere(['id' => $id])->one();

        if ($model instanceof Song) {
            return $model;
        }

        throw new NotFoundHttpException('Песня не найдена.');
    }

    private function handleForm(Song $song, string $view, string $successMessage): string|Response
    {
        $formModel = $this->createFormModel($song);

        if ($formModel->load(Yii::$app->request->post()) && $formModel->validate()) {
            $formModel->save();
            Yii::$app->session->setFlash('success', $successMessage);

            return $this->redirect(['update', 'id' => $formModel->getSong()->id]);
        }

        return $this->render($view, [
            'formModel' => $formModel,
        ]);
    }

    private function createFormModel(Song $song): SongEditorForm
    {
        return new SongEditorForm($song, $this->findLanguages(), $this->findArtists());
    }
}
