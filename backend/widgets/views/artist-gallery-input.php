<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\ArtistGalleryForm $galleryForm */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var string $widgetId */

use yii\bootstrap5\Html;

$formatter = Yii::$app->formatter;
$imageModels = $galleryForm->getImageModels();
$newImageInputName = Html::getInputName($galleryForm, 'newImageFiles') . '[]';
?>
<div class="artist-gallery-widget" data-role="artist-gallery-root" id="<?= Html::encode($widgetId) ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-1">Галерея изображений</h2>
            <p class="text-muted mb-0">Можно загрузить несколько фотографий исполнителя. Основное изображение выбирается среди уже сохранённых.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="mb-3">
                <?= Html::activeLabel($galleryForm, 'newImageFiles', ['class' => 'form-label']) ?>
                <?= Html::fileInput($newImageInputName, null, [
                    'accept' => 'image/*',
                    'multiple' => true,
                    'class' => 'form-control',
                    'data-role' => 'artist-gallery-input',
                ]) ?>
                <?= Html::error($galleryForm, 'newImageFiles', ['class' => 'invalid-feedback d-block']) ?>
            </div>
            <div class="form-text">
                Поддерживаются `jpg`, `png`, `gif`, `webp`. Если галерея пока пустая, первое новое изображение станет основным автоматически.
            </div>
        </div>
        <div class="col-xl-8">
            <div class="artist-gallery-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Текущие изображения</h3>
                    <span class="text-muted small">Выбери основное, порядок и изображения на удаление</span>
                </div>

                <div class="artist-gallery-empty-state<?= $imageModels === [] ? '' : ' d-none' ?>" data-role="artist-gallery-existing-empty">
                    Изображения ещё не загружены.
                </div>

                <div class="artist-gallery-grid<?= $imageModels === [] ? ' d-none' : '' ?>" data-role="artist-gallery-existing-list">
                    <?php foreach ($imageModels as $index => $imageModel): ?>
                        <div class="artist-gallery-card" data-role="artist-gallery-existing-item">
                            <?= Html::activeHiddenInput($imageModel, '[' . $index . ']mediaAssetId') ?>
                            <?= Html::activeHiddenInput($imageModel, '[' . $index . ']deleteImage', [
                                'data-role' => 'artist-gallery-delete-input',
                                'value' => $imageModel->deleteImage ? '1' : '0',
                            ]) ?>

                            <div class="artist-gallery-card-preview">
                                <img
                                    alt="<?= Html::encode($imageModel->originalName) ?>"
                                    class="artist-gallery-card-image"
                                    src="<?= Html::encode($imageModel->publicUrl) ?>"
                                >
                            </div>

                            <div class="artist-gallery-card-body">
                                <div class="fw-semibold text-truncate mb-1"><?= Html::encode($imageModel->originalName) ?></div>
                                <div class="text-muted small mb-3">
                                    <?php
                                    $meta = [];

                                    if ($imageModel->mimeType !== '') {
                                        $meta[] = $imageModel->mimeType;
                                    }

                                    if ($imageModel->sizeBytes !== null) {
                                        $meta[] = $formatter->asShortSize($imageModel->sizeBytes, 1);
                                    }

                                    if ($imageModel->width !== null && $imageModel->height !== null) {
                                        $meta[] = $imageModel->width . '×' . $imageModel->height;
                                    }
                                    ?>
                                    <?= Html::encode(implode(' · ', $meta)) ?>
                                </div>

                                <div class="row g-3 align-items-end">
                                    <div class="col-sm-5">
                                        <?= $form->field($imageModel, '[' . $index . ']sortOrder')->textInput([
                                            'data-role' => 'artist-gallery-sort-order',
                                            'inputmode' => 'numeric',
                                        ]) ?>
                                    </div>
                                    <div class="col-sm-7">
                                        <label class="form-label d-block">Статус</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <label class="btn btn-outline-primary btn-sm artist-gallery-toggle">
                                                <?= Html::activeRadio($galleryForm, 'primaryMediaAssetId', [
                                                    'class' => 'artist-gallery-primary-input',
                                                    'data-role' => 'artist-gallery-primary-radio',
                                                    'label' => false,
                                                    'uncheck' => null,
                                                    'value' => $imageModel->mediaAssetId,
                                                    'checked' => (string) $galleryForm->primaryMediaAssetId === (string) $imageModel->mediaAssetId,
                                                ]) ?>
                                                Основное
                                            </label>
                                            <button
                                                class="btn btn-outline-danger btn-sm"
                                                data-role="artist-gallery-delete-toggle"
                                                type="button"
                                            >
                                                Удалить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="artist-gallery-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Новые изображения</h3>
                    <span class="text-muted small">Предпросмотр до сохранения</span>
                </div>

                <div class="artist-gallery-empty-state" data-role="artist-gallery-new-empty">
                    Выбери файлы слева, и здесь появится предпросмотр.
                </div>
                <div class="artist-gallery-grid d-none" data-role="artist-gallery-new-list"></div>
            </div>
        </div>
    </div>
</div>
