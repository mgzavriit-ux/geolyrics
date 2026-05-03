<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\GeorgianTransliterationForm;
use common\models\Language;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;

final class TransliterationController extends AdminController
{
    public function actionIndex(): string|Response
    {
        $formModel = new GeorgianTransliterationForm($this->findLanguages());

        if ($formModel->load(Yii::$app->request->post()) && $formModel->validate()) {
            $formModel->save();
            Yii::$app->session->setFlash('success', 'Транслитерация обновлена.');

            return $this->redirect(['index']);
        }

        return $this->render('index', [
            'formModel' => $formModel,
        ]);
    }

    /**
     * @return Language[]
     */
    private function findLanguages(): array
    {
        return Language::find()
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }
}
