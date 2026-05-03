<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Recording $model */
/** @var backend\models\RecordingMediaUploadForm $uploadForm */
/** @var array $publicationStatusItems */
/** @var array $recordingTypeItems */
/** @var array $songItems */

use backend\assets\RecordingMediaAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

RecordingMediaAsset::register($this);

$audioMediaAsset = $model->getAudioMediaAsset();
$videoMediaAsset = $model->getVideoMediaAsset();
$coverMediaAsset = $model->coverMediaAsset;
$formatter = \Yii::$app->formatter;

?>
<div class="recording-form" data-role="recording-media-root">
    <?php $form = ActiveForm::begin([
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]); ?>

    <div class="border rounded p-3 mb-4">
        <h2 class="h4 mb-3">Основное</h2>

        <div class="row g-3">
            <div class="col-lg-6">
                <?= $form->field($model, 'song_id')->dropDownList($songItems, ['prompt' => 'Выберите песню']) ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'default_title')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-md-6 col-lg-4">
                <?= $form->field($model, 'recording_type')->dropDownList($recordingTypeItems, [
                    'prompt' => 'Выберите тип',
                    'data-role' => 'recording-type-select',
                ]) ?>
            </div>
            <div class="col-md-6 col-lg-4">
                <?= $form->field($model, 'publication_status')->dropDownList($publicationStatusItems, ['prompt' => 'Выберите статус']) ?>
            </div>
            <div class="col-lg-4">
                <div class="mb-3">
                    <?= Html::activeLabel($model, 'slug', ['class' => 'form-label']) ?>
                    <?= Html::textInput('recordingSlugDisplay', (string) $model->slug, [
                        'class' => 'form-control',
                        'readonly' => true,
                        'disabled' => true,
                    ]) ?>
                    <div class="form-text">Slug записи формируется автоматически как `slug-песни-audio` или `slug-песни-video`.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="border rounded p-3 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">Медиа</h2>
                <p class="text-muted mb-0">Показывается только нужный основной файл для выбранного типа записи. Новый файл заменит текущий.</p>
            </div>
        </div>

        <div class="alert alert-light border mb-3" data-role="recording-media-empty">
            Сначала выбери тип записи, чтобы прикрепить основной медиафайл.
        </div>

        <div class="border rounded p-3 mb-3 d-none" data-role="recording-media-audio">
            <h3 class="h6 mb-3">Основной аудиофайл</h3>
            <?= $form->field($uploadForm, 'audioFile')->fileInput(['accept' => 'audio/*']) ?>

            <?php if ($audioMediaAsset !== null): ?>
                <div class="bg-light border rounded p-3">
                    <div class="fw-semibold mb-1"><?= Html::encode($audioMediaAsset->original_name) ?></div>
                    <div class="small text-muted mb-2">
                        <?= Html::encode($audioMediaAsset->mime_type ?? 'mime неизвестен') ?>
                        <?php if ($audioMediaAsset->size_bytes !== null): ?>
                            · <?= Html::encode($formatter->asShortSize((int) $audioMediaAsset->size_bytes, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <?= Html::a(
                        'Открыть файл',
                        \Yii::$app->storage->getPublicUrl($audioMediaAsset->path),
                        ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
                    ) ?>
                </div>
            <?php else: ?>
                <div class="text-muted small">Аудиофайл пока не загружен.</div>
            <?php endif; ?>
        </div>

        <div class="border rounded p-3 mb-3 d-none" data-role="recording-media-video">
            <h3 class="h6 mb-3">Основной видеофайл</h3>
            <?= $form->field($uploadForm, 'videoFile')->fileInput(['accept' => 'video/*']) ?>

            <?php if ($videoMediaAsset !== null): ?>
                <div class="bg-light border rounded p-3">
                    <div class="fw-semibold mb-1"><?= Html::encode($videoMediaAsset->original_name) ?></div>
                    <div class="small text-muted mb-2">
                        <?= Html::encode($videoMediaAsset->mime_type ?? 'mime неизвестен') ?>
                        <?php if ($videoMediaAsset->size_bytes !== null): ?>
                            · <?= Html::encode($formatter->asShortSize((int) $videoMediaAsset->size_bytes, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <?= Html::a(
                        'Открыть файл',
                        \Yii::$app->storage->getPublicUrl($videoMediaAsset->path),
                        ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
                    ) ?>
                </div>
            <?php else: ?>
                <div class="text-muted small">Видеофайл пока не загружен.</div>
            <?php endif; ?>
        </div>

        <div class="border-top pt-3">
            <h3 class="h6 mb-3">Обложка</h3>
            <div class="row g-3 align-items-start">
                <div class="col-lg-7">
                    <?= $form->field($uploadForm, 'coverFile')->fileInput(['accept' => 'image/*']) ?>
                </div>
                <div class="col-lg-5">
                    <?php if ($coverMediaAsset !== null): ?>
                        <div class="bg-light border rounded p-3">
                            <div class="mb-2">
                                <?= Html::img(
                                    \Yii::$app->storage->getPublicUrl($coverMediaAsset->path),
                                    ['class' => 'img-fluid rounded border', 'style' => 'max-height: 180px;']
                                ) ?>
                            </div>
                            <div class="fw-semibold small mb-1"><?= Html::encode($coverMediaAsset->original_name) ?></div>
                            <?= Html::a(
                                'Открыть изображение',
                                \Yii::$app->storage->getPublicUrl($coverMediaAsset->path),
                                ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
                            ) ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small">Обложка пока не загружена.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="border rounded p-3 mb-4">
        <h2 class="h4 mb-3">Дополнительно</h2>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <?= $form->field($model, 'release_year')->input('number') ?>
            </div>
            <div class="col-md-6 col-lg-4">
                <?= $form->field($model, 'duration_ms')->input('number') ?>
            </div>
            <div class="col-lg-4">
                <div class="mb-3">
                    <?= Html::activeLabel($model, 'published_at', ['class' => 'form-label']) ?>
                    <?= Html::textInput('recordingPublishedAtDisplay', $model->getPublishedAtFormatted(), [
                        'class' => 'form-control',
                        'readonly' => true,
                        'disabled' => true,
                    ]) ?>
                    <div class="form-text">Дата выставляется автоматически при первом сохранении со статусом «Опубликована».</div>
                </div>
            </div>
        </div>

        <?= $form->field($model, 'description')->textarea(['rows' => 5]) ?>

        <?= $form->field($model, 'chords_text')->textarea(['rows' => 5]) ?>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/recording/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
