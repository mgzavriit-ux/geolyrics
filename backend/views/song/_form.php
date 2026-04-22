<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Song $model */
/** @var array $languageItems */
/** @var array $publicationStatusItems */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="song-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'original_language_id')->dropDownList($languageItems, ['prompt' => 'Выберите язык']) ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'default_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'publication_status')->dropDownList($publicationStatusItems, ['prompt' => 'Выберите статус']) ?>

    <?= $form->field($model, 'cover_media_asset_id')->input('number') ?>

    <?= $form->field($model, 'published_at')->input('number') ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/song/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
