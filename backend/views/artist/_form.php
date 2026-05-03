<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Artist $model */
/** @var array $publicationStatusItems */
/** @var array $typeItems */
/** @var common\models\ArtistTranslation[] $translationModels */
/** @var array $languageLabels */

use common\models\ArtistTranslation;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="artist-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'default_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->dropDownList($typeItems, ['prompt' => 'Выберите тип']) ?>

    <?= $form->field($model, 'publication_status')->dropDownList($publicationStatusItems, ['prompt' => 'Выберите статус']) ?>

    <div class="mb-3">
        <?= Html::activeLabel($model, 'published_at', ['class' => 'form-label']) ?>
        <?= Html::textInput('artistPublishedAtDisplay', $model->getPublishedAtFormatted(), [
            'class' => 'form-control',
            'readonly' => true,
            'disabled' => true,
        ]) ?>
        <div class="form-text">Дата выставляется автоматически при первом сохранении со статусом «Опубликован».</div>
    </div>

    <hr class="my-4">

    <h2 class="h4">Переводы</h2>
    <p class="text-muted">
        Основное имя хранится в поле выше. Здесь можно добавить локализованные названия и биографию по языкам.
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

            <?= $form->field($translationModel, '[' . $index . ']biography')->textarea([
                'rows' => 4,
                'placeholder' => 'Биография на выбранном языке',
            ]) ?>
        </div>
    <?php endforeach; ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/artist/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
