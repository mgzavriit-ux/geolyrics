<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\TagSearch;
use common\models\Language;
use common\models\Tag;
use common\models\TagTranslation;
use Yii;
use yii\base\Model;
use yii\db\IntegrityException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class TagController extends AdminController
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
        $model = new Tag();
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadTagForm($model, $translationModels) && $this->validateTagForm($model, $translationModels)) {
            $this->saveTagForm($model, $translationModels);
            Yii::$app->session->setFlash('success', 'Тег сохранен.');

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
            Yii::$app->session->setFlash('success', 'Тег удален.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash(
                'warning',
                'Тег нельзя удалить, пока он связан с песнями. Сначала отвяжите его от песен.',
            );
        }

        return $this->redirect(['index']);
    }

    public function actionIndex(): string
    {
        $searchModel = new TagSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $tag = new Tag();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'publicationStatusItems' => $tag->getPublicationStatusList(),
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);
        $languages = $this->findLanguages();
        $translationModels = $this->findTranslationModels($model, $languages);

        if ($this->loadTagForm($model, $translationModels) && $this->validateTagForm($model, $translationModels)) {
            $this->saveTagForm($model, $translationModels);
            Yii::$app->session->setFlash('success', 'Тег обновлен.');

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'publicationStatusItems' => $model->getPublicationStatusList(),
            'translationModels' => $translationModels,
            'languageLabels' => $this->findLanguageLabels($languages),
        ]);
    }

    private function findModel(int $id): Tag
    {
        $model = Tag::find()
            ->with(['translations'])
            ->andWhere(['id' => $id])
            ->one();

        if ($model instanceof Tag) {
            return $model;
        }

        throw new NotFoundHttpException('Тег не найден.');
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
     * @return TagTranslation[]
     */
    private function findTranslationModels(Tag $tag, array $languages): array
    {
        $translationByLanguageId = [];

        if ($tag->isNewRecord === false) {
            $translationByLanguageId = TagTranslation::find()
                ->indexBy('language_id')
                ->andWhere(['tag_id' => $tag->id])
                ->all();
        }

        $translationModels = [];

        foreach ($languages as $language) {
            $translationModel = $translationByLanguageId[$language->id] ?? new TagTranslation();
            $translationModel->language_id = $language->id;

            if ($tag->isNewRecord === false) {
                $translationModel->tag_id = $tag->id;
            }

            $translationModels[] = $translationModel;
        }

        return $translationModels;
    }

    /**
     * @param TagTranslation[] $translationModels
     */
    private function loadTagForm(Tag $model, array $translationModels): bool
    {
        if (Yii::$app->request->isPost === false) {
            return false;
        }

        $model->load(Yii::$app->request->post());
        Model::loadMultiple($translationModels, Yii::$app->request->post());

        return true;
    }

    /**
     * @param TagTranslation[] $translationModels
     */
    private function validateTagForm(Tag $model, array $translationModels): bool
    {
        $isValid = $model->validate();

        return Model::validateMultiple($translationModels) && $isValid;
    }

    /**
     * @param TagTranslation[] $translationModels
     */
    private function saveTagForm(Tag $model, array $translationModels): void
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
     * @param TagTranslation[] $translationModels
     */
    private function saveTranslationModels(Tag $tag, array $translationModels): void
    {
        foreach ($translationModels as $translationModel) {
            if ($translationModel->hasContent() === false) {
                if ($translationModel->isNewRecord === false) {
                    $translationModel->delete();
                }

                continue;
            }

            $translationModel->tag_id = $tag->id;
            $translationModel->save(false);
        }
    }
}
