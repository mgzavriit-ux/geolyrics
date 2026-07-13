<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Genre $model */
/** @var array $publicationStatusItems */
/** @var common\models\GenreTranslation[] $translationModels */
/** @var array $languageLabels */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="genre-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'default_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'publication_status')->dropDownList($publicationStatusItems) ?>

    <hr class="my-4">

    <h2 class="h4">Переводы</h2>
    <p class="text-muted">
        Основное название хранится выше. Здесь можно добавить локализованные названия и описания по языкам.
    </p>

    <?php foreach ($translationModels as $index => $translationModel): ?>
        <div class="border rounded p-3 mb-3">
            <h3 class="h5 mb-3"><?= Html::encode($languageLabels[$translationModel->language_id] ?? (string) $translationModel->language_id) ?></h3>

            <?= Html::activeHiddenInput($translationModel, '[' . $index . ']id') ?>
            <?= Html::activeHiddenInput($translationModel, '[' . $index . ']language_id') ?>

            <?= $form->field($translationModel, '[' . $index . ']name')->textInput([
                'maxlength' => true,
                'placeholder' => 'Локализованное название',
            ]) ?>

            <?= $form->field($translationModel, '[' . $index . ']description')->textarea([
                'rows' => 3,
                'placeholder' => 'Описание на выбранном языке',
            ]) ?>
        </div>
    <?php endforeach; ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/genre/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
