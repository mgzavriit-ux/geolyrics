<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var int|string $recordingIndex */
/** @var common\models\Recording $recordingModel */
/** @var backend\models\RecordingMediaUploadForm $recordingUploadForm */
/** @var array<int|string> $artistFlatIndexes */
/** @var array<int|string, bool> $artistVisibilityMap */
/** @var array<int, common\models\RecordingArtist> $recordingArtistFlatModels */
/** @var array<int, string> $artistItems */
/** @var array<string, string> $recordingArtistRoleItems */
/** @var array<string, string> $recordingTypeItems */
/** @var array<string, string> $recordingPublicationStatusItems */
/** @var bool $isDeleted */
/** @var bool $isHidden */

use yii\helpers\Html;

$itemClass = 'recording-item border rounded p-3 mb-4';
$audioMediaAsset = $recordingModel->getAudioMediaAsset();
$videoMediaAsset = $recordingModel->getVideoMediaAsset();
$coverMediaAsset = $recordingModel->coverMediaAsset;
$formatter = \Yii::$app->formatter;

if ($isHidden) {
    $itemClass .= ' d-none';
}
?>
<div
    class="<?= Html::encode($itemClass) ?>"
    data-role="recording-item"
    data-recording-media-root="1"
    data-recording-index="<?= Html::encode((string) $recordingIndex) ?>"
>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0" data-role="recording-title">Запись</h3>
        <?= Html::button('Удалить запись', [
            'class' => 'btn btn-outline-danger btn-sm',
            'type' => 'button',
            'data-role' => 'remove-recording',
        ]) ?>
    </div>

    <?= Html::activeHiddenInput($recordingModel, '[' . $recordingIndex . ']id') ?>
    <?= Html::hiddenInput(
        'recordingDeleteFlags[' . $recordingIndex . ']',
        $isDeleted ? '1' : '0',
        ['data-role' => 'recording-delete-flag'],
    ) ?>

    <div class="border rounded p-3 mb-4">
        <h4 class="h6 mb-3">Основное</h4>

        <div class="row g-3">
            <div class="col-lg-6">
                <?= $form->field($recordingModel, '[' . $recordingIndex . ']default_title')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-md-6 col-lg-3">
                <?= $form->field($recordingModel, '[' . $recordingIndex . ']recording_type')->dropDownList(
                    $recordingTypeItems,
                    [
                        'prompt' => 'Выберите тип',
                        'data-role' => 'recording-type-select',
                    ],
                ) ?>
            </div>
            <div class="col-md-6 col-lg-3">
                <?= $form->field($recordingModel, '[' . $recordingIndex . ']publication_status')->dropDownList(
                    $recordingPublicationStatusItems,
                    ['prompt' => 'Выберите статус'],
                ) ?>
            </div>
            <div class="col-lg-6">
                <div class="mb-3">
                    <?= Html::activeLabel($recordingModel, '[' . $recordingIndex . ']slug', ['class' => 'form-label']) ?>
                    <?= Html::textInput('recordingSlugDisplay[' . $recordingIndex . ']', (string) $recordingModel->slug, [
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
                <h4 class="h6 mb-1">Медиа</h4>
                <p class="text-muted mb-0">Показывается только нужный основной файл для выбранного типа записи. Новый файл заменит текущий.</p>
            </div>
        </div>

        <div class="alert alert-light border mb-3" data-role="recording-media-empty">
            Сначала выбери тип записи, чтобы прикрепить основной медиафайл.
        </div>

        <div class="border rounded p-3 mb-3 d-none" data-role="recording-media-audio">
            <h5 class="h6 mb-3">Основной аудиофайл</h5>
            <?= $form->field($recordingUploadForm, '[' . $recordingIndex . ']audioFile')->fileInput(['accept' => 'audio/*']) ?>

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
            <h5 class="h6 mb-3">Основной видеофайл</h5>
            <?= $form->field($recordingUploadForm, '[' . $recordingIndex . ']videoFile')->fileInput(['accept' => 'video/*']) ?>

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
            <h5 class="h6 mb-3">Обложка</h5>
            <div class="row g-3 align-items-start">
                <div class="col-lg-7">
                    <?= $form->field($recordingUploadForm, '[' . $recordingIndex . ']coverFile')->fileInput(['accept' => 'image/*']) ?>
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
        <h4 class="h6 mb-3">Дополнительно</h4>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <?= $form->field($recordingModel, '[' . $recordingIndex . ']release_year')->input('number') ?>
            </div>
            <div class="col-md-6 col-lg-4">
                <?= $form->field($recordingModel, '[' . $recordingIndex . ']duration_ms')->input('number') ?>
            </div>
            <div class="col-lg-4">
                <div class="mb-3">
                    <?= Html::activeLabel($recordingModel, '[' . $recordingIndex . ']published_at', ['class' => 'form-label']) ?>
                    <?= Html::textInput('recordingPublishedAtDisplay[' . $recordingIndex . ']', $recordingModel->getPublishedAtFormatted(), [
                        'class' => 'form-control',
                        'readonly' => true,
                        'disabled' => true,
                    ]) ?>
                    <div class="form-text">Дата выставляется автоматически при первом сохранении со статусом «Опубликована».</div>
                </div>
            </div>
        </div>

        <?= $form->field($recordingModel, '[' . $recordingIndex . ']description')->textarea(['rows' => 4]) ?>

        <?= $form->field($recordingModel, '[' . $recordingIndex . ']chords_text')->textarea(['rows' => 4]) ?>
    </div>

    <div class="border-top pt-3 mt-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
                <h4 class="h6 mb-1">Исполнители записи</h4>
                <p class="text-muted mb-0">Показываются только заполненные исполнители. Новые строки добавляются кнопкой ниже.</p>
            </div>
            <div>
                <?= Html::button('Добавить исполнителя', [
                    'class' => 'btn btn-outline-secondary btn-sm',
                    'type' => 'button',
                    'data-role' => 'add-recording-artist',
                ]) ?>
            </div>
        </div>

        <div data-role="recording-artist-items">
            <?php foreach ($artistFlatIndexes as $artistFlatIndex): ?>
                <?= $this->render('_recording_artist_fields', [
                    'form' => $form,
                    'artistFlatIndex' => $artistFlatIndex,
                    'recordingIndex' => $recordingIndex,
                    'artistModel' => $recordingArtistFlatModels[$artistFlatIndex],
                    'artistItems' => $artistItems,
                    'roleItems' => $recordingArtistRoleItems,
                    'isHidden' => ($artistVisibilityMap[$artistFlatIndex] ?? false) === false,
                ]) ?>
            <?php endforeach; ?>
        </div>

        <div class="text-muted<?= $artistFlatIndexes === [] ? '' : ' d-none' ?>" data-role="recording-artist-empty-state">
            Исполнители для этой записи пока не указаны.
        </div>
    </div>
</div>
