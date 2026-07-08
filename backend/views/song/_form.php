<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\SongEditorForm $formModel */

use backend\assets\SongEditorAsset;
use backend\assets\RecordingMediaAsset;
use backend\models\RecordingMediaUploadForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

SongEditorAsset::register($this);
RecordingMediaAsset::register($this);

$songId = $formModel->getSong()->id;
$song = $formModel->getSong();
$songCoverUploadForm = $formModel->getSongCoverUploadForm();
$songCoverMediaAsset = $song->coverMediaAsset;
$songCoverMediaUrl = $songCoverMediaAsset === null ? null : \Yii::$app->storage->getPublicUrl($songCoverMediaAsset->path);
$formatter = \Yii::$app->formatter;
$languageLabels = $formModel->getLanguageLabels();
$languageItems = $formModel->getLanguageItems();
$songTranslationLanguageItems = $formModel->getSongTranslationLanguageItems();
$initialSongTranslationLanguageId = $formModel->getInitialSongTranslationLanguageId();
$songTextLanguageItems = $formModel->getSongTextLanguageItems();
$initialSongTextLanguageId = $formModel->getInitialSongTextLanguageId();
$songArrangementModels = $formModel->getSongArrangementModels();
$songArrangementVisibleIndexes = $formModel->getSongArrangementVisibleIndexes();
$songLineModels = $formModel->getSongLineModels();
$songLineVisibleIndexes = $formModel->getSongLineVisibleIndexes();
$songLineTranslationLanguageItems = $formModel->getSongLineTranslationLanguageItems();
$songLineTranslationFlatModels = $formModel->getSongLineTranslationFlatModels();
$recordingModels = $formModel->getRecordingModels();
$recordingVisibleIndexes = $formModel->getRecordingVisibleIndexes();
$recordingArtistFlatModels = $formModel->getRecordingArtistFlatModels();
$preloadAllSongTranslationPanels = \Yii::$app->request->isPost;
$preloadAllSongTextPanels = \Yii::$app->request->isPost;
$renderAllSongArrangementModels = \Yii::$app->request->isPost;
$renderAllSongLineModels = \Yii::$app->request->isPost;
$renderAllRecordingModels = \Yii::$app->request->isPost;
$translationFieldsUrl = Url::to(['/song/translation-fields']);
$textFieldsUrl = Url::to(['/song/text-fields']);
$isSongLinesExpanded = $formModel->hasSongLineErrors();
$songLineTemplateModel = new \common\models\SongLine();
$songLineTemplateTranslationModels = [];
$songLineTemplateTranslationIndexes = [];

foreach (array_keys($songLineTranslationLanguageItems) as $translationOffset => $languageId) {
    $translationModel = new \common\models\SongLineTranslation();
    $translationModel->language_id = $languageId;
    $songLineTemplateTranslationModels[$languageId] = $translationModel;
    $songLineTemplateTranslationIndexes[$languageId] = '__translation_index_' . $translationOffset . '__';
}

$songArrangementTemplateModel = new \common\models\SongArrangement();
$songArrangementTemplateModel->source_format = \common\models\SongArrangement::FORMAT_CHORD_PRO;
$recordingTemplateModel = new \common\models\Recording();
$recordingTemplateModel->scenario = \common\models\Recording::SCENARIO_EMBEDDED_SONG;
$recordingArtistTemplateModel = new \common\models\RecordingArtist();
$recordingMediaUploadTemplateForm = new RecordingMediaUploadForm();
$nextSongArrangementIndex = $songArrangementModels === [] ? 0 : max(array_keys($songArrangementModels)) + 1;
$nextRecordingIndex = $recordingModels === [] ? 0 : max(array_keys($recordingModels)) + 1;
$nextRecordingArtistFlatIndex = $recordingArtistFlatModels === [] ? 0 : max(array_keys($recordingArtistFlatModels)) + 1;
?>
<div
    class="song-form"
    data-song-editor
    data-song-id="<?= Html::encode((string) ($songId ?? '')) ?>"
    data-translation-url="<?= Html::encode($translationFieldsUrl) ?>"
    data-text-url="<?= Html::encode($textFieldsUrl) ?>"
>
    <?php $form = ActiveForm::begin([
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]); ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="border rounded p-3 h-100">
                <h2 class="h4">Основное</h2>

                <?= $form->field($song, 'original_language_id')->dropDownList($languageItems, ['prompt' => 'Выберите язык']) ?>

                <?= $form->field($song, 'default_title')->textInput(['maxlength' => true]) ?>

                <div class="mb-3">
                    <?= Html::activeLabel($song, 'slug', ['class' => 'form-label']) ?>
                    <?= Html::textInput('songSlugDisplay', (string) $song->slug, [
                        'class' => 'form-control',
                        'readonly' => true,
                        'disabled' => true,
                    ]) ?>
                    <div class="form-text">Slug формируется автоматически при первом сохранении песни из основного названия.</div>
                </div>

                <?= $form->field($song, 'publication_status')->dropDownList($formModel->getPublicationStatusItems()) ?>

                <div class="border rounded p-3 mb-3">
                    <h3 class="h6 mb-3">Обложка песни</h3>
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-7">
                            <?= $form->field($songCoverUploadForm, 'coverFile')->fileInput(['accept' => 'image/*']) ?>
                            <div class="form-text">Новая картинка заменит текущую обложку песни.</div>
                        </div>
                        <div class="col-lg-5">
                            <?php if ($songCoverMediaAsset !== null): ?>
                                <div class="bg-light border rounded p-3">
                                    <div class="mb-2">
                                        <?= Html::img(
                                            $songCoverMediaUrl,
                                            ['class' => 'img-fluid rounded border', 'style' => 'max-height: 180px;']
                                        ) ?>
                                    </div>
                                    <div class="fw-semibold small mb-1"><?= Html::encode($songCoverMediaAsset->original_name) ?></div>
                                    <div class="small text-muted mb-2">
                                        <?= Html::encode($songCoverMediaAsset->mime_type ?? 'mime неизвестен') ?>
                                        <?php if ($songCoverMediaAsset->size_bytes !== null): ?>
                                            · <?= Html::encode($formatter->asShortSize((int) $songCoverMediaAsset->size_bytes, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?= Html::a(
                                        'Открыть изображение',
                                        $songCoverMediaUrl,
                                        ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
                                    ) ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">Обложка песни пока не загружена.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?= $form->field($formModel, 'publishedAtInputValue')
                    ->input('datetime-local', ['step' => 1])
                    ->hint('Можно скорректировать вручную. Если оставить пустым у опубликованной песни, дата выставится автоматически при сохранении.') ?>
            </div>
        </div>

        <div class="col-xl-6 d-flex">
            <div class="border rounded p-3 d-flex flex-column flex-grow-1">
                <div class="gap-3 mb-3">
                    <div>
                        <h2 class="h4">Переводы названия и описания</h2>
                    </div>
                    <div class="w-100 flex-shrink-0" style="min-width: 260px;">
                        <?= Html::label('Направление перевода', 'song-translation-language', ['class' => 'form-label']) ?>
                        <?= Html::dropDownList(
                            'songTranslationLanguage',
                            $initialSongTranslationLanguageId,
                            $songTranslationLanguageItems,
                            [
                                'id' => 'song-translation-language',
                                'class' => 'form-select',
                                'prompt' => 'Выберите язык',
                                'data-role' => 'translation-language-select',
                            ],
                        ) ?>
                    </div>
                </div>

                <div class="flex-grow-1" data-role="translation-panels">
                    <?php if ($songTranslationLanguageItems !== []): ?>
                        <?php foreach ($songTranslationLanguageItems as $languageId => $languageLabel): ?>
                            <?php
                            $translationModel = $formModel->getSongTranslationModelByLanguageId((int) $languageId);
                            $translationIndex = $formModel->getSongTranslationInputIndex((int) $languageId);

                            if ($translationModel === null || $translationIndex === null) {
                                continue;
                            }

                            if ($preloadAllSongTranslationPanels === false && (int) $languageId !== $initialSongTranslationLanguageId) {
                                continue;
                            }

                            $panelHtml = $this->render('_translation_fields', [
                                'languageId' => (int) $languageId,
                                'languageLabel' => $languageLabel,
                                'isVisible' => (int) $languageId === $initialSongTranslationLanguageId,
                                'translationIndex' => $translationIndex,
                                'translationModel' => $translationModel,
                            ]);
                            ?>
                            <?= $panelHtml ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">Нет доступных языков перевода.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="border rounded p-3 mb-4">
        <div class="gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">Полный текст песни</h2>
                <p class="text-muted mb-0">Заполняй текст целиком по каждому языку. При сохранении он автоматически разложится по строкам.</p>
            </div>
            <div class="w-100 flex-shrink-0" style="min-width: 260px;">
                <?= Html::label('Язык текста', 'song-text-language', ['class' => 'form-label']) ?>
                <?= Html::dropDownList(
                    'songTextLanguage',
                    $initialSongTextLanguageId,
                    $songTextLanguageItems,
                    [
                        'id' => 'song-text-language',
                        'class' => 'form-select',
                        'prompt' => 'Выберите язык',
                        'data-role' => 'song-text-language-select',
                    ],
                ) ?>
            </div>
        </div>

        <div data-role="song-text-panels">
            <?php if ($songTextLanguageItems !== []): ?>
                <?php foreach ($songTextLanguageItems as $languageId => $languageLabel): ?>
                    <?php
                    if ($preloadAllSongTextPanels === false && (int) $languageId !== $initialSongTextLanguageId) {
                        continue;
                    }
                    ?>
                    <?= $this->render('_song_text_fields', [
                        'languageId' => (int) $languageId,
                        'languageLabel' => $languageLabel,
                        'isVisible' => (int) $languageId === $initialSongTextLanguageId,
                        'textValue' => $formModel->getSongTextValueByLanguageId((int) $languageId),
                    ]) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">Нет доступных языков текста.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="accordion mb-4" id="song-lines-accordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="song-lines-heading">
                <button
                    class="accordion-button<?= $isSongLinesExpanded ? '' : ' collapsed' ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#song-lines-collapse"
                    aria-expanded="<?= $isSongLinesExpanded ? 'true' : 'false' ?>"
                    aria-controls="song-lines-collapse"
                >
                    <span>Строки песни</span>
                    <span class="badge text-bg-secondary ms-3" data-role="song-line-count"><?= Html::encode((string) count($songLineVisibleIndexes)) ?></span>
                </button>
            </h2>
            <div
                id="song-lines-collapse"
                class="accordion-collapse collapse<?= $isSongLinesExpanded ? ' show' : '' ?>"
                aria-labelledby="song-lines-heading"
                data-bs-parent="#song-lines-accordion"
            >
                <div class="accordion-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                        <p class="text-muted mb-0">Пустые строки не показываются и не сохраняются. После сохранения можно открыть этот блок и вручную поправить отдельные строки.</p>
                        <div>
                            <?= Html::button('Добавить строку', [
                                'class' => 'btn btn-outline-secondary',
                                'type' => 'button',
                                'data-role' => 'add-song-line',
                            ]) ?>
                        </div>
                    </div>

                    <div
                        data-role="song-line-items"
                        data-next-line-index="<?= Html::encode((string) count($songLineModels)) ?>"
                        data-translation-language-count="<?= Html::encode((string) count($songLineTranslationLanguageItems)) ?>"
                    >
                        <?php foreach (array_keys($songLineModels) as $lineIndex): ?>
                            <?php
                            $isVisibleSongLine = $formModel->shouldRenderSongLine($lineIndex);

                            if ($renderAllSongLineModels === false && $isVisibleSongLine === false) {
                                continue;
                            }

                            $translationModels = [];
                            $translationIndexes = [];

                            foreach ($formModel->getSongLineTranslationInputIndexes($lineIndex) as $languageId => $translationFlatIndex) {
                                $translationModels[$languageId] = $songLineTranslationFlatModels[$translationFlatIndex];
                                $translationIndexes[$languageId] = $translationFlatIndex;
                            }
                            ?>
                            <?= $this->render('_song_line_fields', [
                                'lineIndex' => $lineIndex,
                                'lineModel' => $songLineModels[$lineIndex],
                                'translationModels' => $translationModels,
                                'translationIndexes' => $translationIndexes,
                                'languageLabels' => $languageLabels,
                                'isHidden' => $isVisibleSongLine === false,
                            ]) ?>
                        <?php endforeach; ?>
                    </div>

                    <div
                        class="text-muted<?= $songLineVisibleIndexes === [] ? '' : ' d-none' ?>"
                        data-role="song-line-empty-state"
                    >
                        Строк пока нет. Добавь первую строку вручную или разложи текст автоматически.
                    </div>

                    <template data-role="song-line-template">
                        <?= $this->render('_song_line_fields', [
                            'lineIndex' => '__line_index__',
                            'lineModel' => $songLineTemplateModel,
                            'translationModels' => $songLineTemplateTranslationModels,
                            'translationIndexes' => $songLineTemplateTranslationIndexes,
                            'languageLabels' => $languageLabels,
                            'isHidden' => false,
                        ]) ?>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="border rounded p-3 mb-4">
        <h2 class="h4">Аранжировки и аккорды</h2>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <p class="text-muted mb-0">Аккорды теперь хранятся на уровне песни. Если у одной песни несколько гармонических вариантов, оформи их как отдельные аранжировки.</p>
            <div>
                <?= Html::button('Добавить аранжировку', [
                    'class' => 'btn btn-outline-secondary',
                    'type' => 'button',
                    'data-role' => 'add-song-arrangement',
                ]) ?>
            </div>
        </div>

        <div
            data-role="song-arrangement-items"
            data-next-arrangement-index="<?= Html::encode((string) $nextSongArrangementIndex) ?>"
        >
            <?php foreach (array_keys($songArrangementModels) as $arrangementIndex): ?>
                <?php
                $isVisibleArrangement = $formModel->shouldRenderSongArrangement($arrangementIndex);

                if ($renderAllSongArrangementModels === false && $isVisibleArrangement === false) {
                    continue;
                }
                ?>
                <?= $this->render('_song_arrangement_fields', [
                    'form' => $form,
                    'arrangementIndex' => $arrangementIndex,
                    'arrangementModel' => $songArrangementModels[$arrangementIndex],
                    'formatItems' => $formModel->getSongArrangementFormatItems(),
                    'isHidden' => $isVisibleArrangement === false,
                ]) ?>
            <?php endforeach; ?>
        </div>

        <div
            class="text-muted<?= $songArrangementVisibleIndexes === [] ? '' : ' d-none' ?>"
            data-role="song-arrangement-empty-state"
        >
            Аранжировок пока нет. Добавь первую, если для песни нужны аккорды.
        </div>

        <template data-role="song-arrangement-template">
            <?= $this->render('_song_arrangement_fields', [
                'form' => $form,
                'arrangementIndex' => '__arrangement_index__',
                'arrangementModel' => $songArrangementTemplateModel,
                'formatItems' => $formModel->getSongArrangementFormatItems(),
                'isHidden' => false,
            ]) ?>
        </template>
    </div>

    <div class="border rounded p-3 mb-4">
        <h2 class="h4">Записи и исполнители</h2>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <p class="text-muted mb-0">Пустые записи и исполнители не показываются и не сохраняются. Новые записи и их исполнители добавляются кнопками ниже.</p>
            <div>
                <?= Html::button('Добавить запись', [
                    'class' => 'btn btn-outline-secondary',
                    'type' => 'button',
                    'data-role' => 'add-recording',
                ]) ?>
            </div>
        </div>

        <div
            data-role="recording-items"
            data-next-recording-index="<?= Html::encode((string) $nextRecordingIndex) ?>"
            data-next-recording-artist-flat-index="<?= Html::encode((string) $nextRecordingArtistFlatIndex) ?>"
        >
            <?php foreach (array_keys($recordingModels) as $recordingIndex): ?>
                <?php
                $isVisibleRecording = $formModel->shouldRenderRecording($recordingIndex);
                $isDeletedRecording = $formModel->isRecordingMarkedForDelete($recordingIndex);

                if ($renderAllRecordingModels === false && $isVisibleRecording === false && $isDeletedRecording === false) {
                    continue;
                }

                $allArtistFlatIndexes = $formModel->getRecordingArtistInputIndexes($recordingIndex);
                $visibleArtistFlatIndexes = $formModel->getRecordingArtistVisibleFlatIndexes($recordingIndex);

                if ($renderAllRecordingModels) {
                    $artistFlatIndexes = $allArtistFlatIndexes;
                } else {
                    $artistFlatIndexes = $visibleArtistFlatIndexes;
                }

                $artistVisibilityMap = [];

                foreach ($artistFlatIndexes as $artistFlatIndex) {
                    $artistVisibilityMap[$artistFlatIndex] = in_array($artistFlatIndex, $visibleArtistFlatIndexes, true);
                }
                ?>
                <?= $this->render('_recording_fields', [
                    'form' => $form,
                    'recordingIndex' => $recordingIndex,
                    'recordingModel' => $recordingModels[$recordingIndex],
                    'recordingUploadForm' => $formModel->getRecordingMediaUploadForm($recordingIndex),
                    'artistFlatIndexes' => $artistFlatIndexes,
                    'artistVisibilityMap' => $artistVisibilityMap,
                    'recordingArtistFlatModels' => $recordingArtistFlatModels,
                    'artistItems' => $formModel->getArtistItems(),
                    'recordingArtistRoleItems' => $formModel->getRecordingArtistRoleItems(),
                    'recordingTypeItems' => $formModel->getRecordingTypeItems(),
                    'recordingPublicationStatusItems' => $formModel->getRecordingPublicationStatusItems(),
                    'isDeleted' => $isDeletedRecording,
                    'isHidden' => $isVisibleRecording === false,
                ]) ?>
            <?php endforeach; ?>
        </div>

        <div
            class="text-muted<?= $recordingVisibleIndexes === [] ? '' : ' d-none' ?>"
            data-role="recording-empty-state"
        >
            Записей пока нет. Добавь первую запись вручную.
        </div>

        <template data-role="recording-template">
                <?= $this->render('_recording_fields', [
                    'form' => $form,
                    'recordingIndex' => '__recording_index__',
                    'recordingModel' => $recordingTemplateModel,
                    'recordingUploadForm' => $recordingMediaUploadTemplateForm,
                    'artistFlatIndexes' => [],
                    'artistVisibilityMap' => [],
                    'recordingArtistFlatModels' => [],
                'artistItems' => $formModel->getArtistItems(),
                'recordingArtistRoleItems' => $formModel->getRecordingArtistRoleItems(),
                'recordingTypeItems' => $formModel->getRecordingTypeItems(),
                'recordingPublicationStatusItems' => $formModel->getRecordingPublicationStatusItems(),
                'isDeleted' => false,
                'isHidden' => false,
            ]) ?>
        </template>

        <template data-role="recording-artist-template">
            <?= $this->render('_recording_artist_fields', [
                'form' => $form,
                'artistFlatIndex' => '__artist_flat_index__',
                'recordingIndex' => '__recording_index__',
                'artistModel' => $recordingArtistTemplateModel,
                'artistItems' => $formModel->getArtistItems(),
                'roleItems' => $formModel->getRecordingArtistRoleItems(),
                'isHidden' => false,
            ]) ?>
        </template>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/song/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
