<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\HomeHeroImageForm $formModel */
/** @var array $artistItems */

use backend\assets\HomeHeroImageAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

HomeHeroImageAsset::register($this);

$previewClass = $formModel->imageUrl === '' ? ' d-none' : '';
$emptyClass = $formModel->imageUrl === '' ? '' : ' d-none';
?>
<div class="home-hero-image-form" data-role="home-hero-image-root">
    <?php $form = ActiveForm::begin([
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]); ?>

    <?= $form->field($formModel, 'artistId')->dropDownList($artistItems, ['prompt' => 'Выберите исполнителя']) ?>

    <?= $form->field($formModel, 'imageFile')->fileInput([
        'accept' => 'image/*',
        'data-role' => 'home-hero-image-input',
    ]) ?>

    <div
        class="home-hero-image-focal mb-3"
    >
        <div class="home-hero-image-focal-preview<?= Html::encode($previewClass) ?>" data-role="home-hero-image-preview">
            <img
                alt="<?= Html::encode($formModel->imageName) ?>"
                class="home-hero-image-focal-image"
                data-role="home-hero-image"
                <?php if ($formModel->imageUrl !== ''): ?>
                    src="<?= Html::encode($formModel->imageUrl) ?>"
                <?php endif; ?>
            >
            <span
                class="home-hero-image-focal-marker"
                data-role="home-hero-image-marker"
                style="left: <?= Html::encode((string) $formModel->focalPointX) ?>%; top: <?= Html::encode((string) $formModel->focalPointY) ?>%;"
            ></span>
        </div>
        <div class="home-hero-image-empty-state<?= Html::encode($emptyClass) ?>" data-role="home-hero-image-empty">
            Фото не выбрано
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6">
            <?= $form->field($formModel, 'focalPointX')->textInput([
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'data-role' => 'home-hero-image-focal-x',
            ]) ?>
        </div>
        <div class="col-12 col-md-6">
            <?= $form->field($formModel, 'focalPointY')->textInput([
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'data-role' => 'home-hero-image-focal-y',
            ]) ?>
        </div>
    </div>

    <?= $form->field($formModel, 'sortOrder')->textInput(['type' => 'number']) ?>

    <?= $form->field($formModel, 'isActive')->checkbox() ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/home-hero-image/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
