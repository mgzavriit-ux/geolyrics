<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Recording $model */
/** @var array $publicationStatusItems */
/** @var array $recordingTypeItems */
/** @var array $songItems */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="recording-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'song_id')->dropDownList($songItems, ['prompt' => 'Выберите песню']) ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'default_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'recording_type')->dropDownList($recordingTypeItems, ['prompt' => 'Выберите тип']) ?>

    <?= $form->field($model, 'publication_status')->dropDownList($publicationStatusItems, ['prompt' => 'Выберите статус']) ?>

    <?= $form->field($model, 'cover_media_asset_id')->input('number') ?>

    <?= $form->field($model, 'release_year')->input('number') ?>

    <?= $form->field($model, 'duration_ms')->input('number') ?>

    <?= $form->field($model, 'chords_text')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'published_at')->input('number') ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/recording/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
