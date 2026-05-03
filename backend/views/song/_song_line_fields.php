<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int|string $lineIndex */
/** @var common\models\SongLine $lineModel */
/** @var array<int, common\models\SongLineTranslation> $translationModels */
/** @var array<int, int|string> $translationIndexes */
/** @var array<int, string> $languageLabels */
/** @var bool $isHidden */

use yii\helpers\Html;

$lineIdAttribute = '[' . $lineIndex . ']id';
$lineTextAttribute = '[' . $lineIndex . ']original_text';
$lineInputClass = 'form-control' . ($lineModel->hasErrors('original_text') ? ' is-invalid' : '');
$itemClass = 'song-line-item border rounded p-3 mb-3';

if ($isHidden) {
    $itemClass .= ' d-none';
}
?>
<div
    class="<?= Html::encode($itemClass) ?>"
    data-role="song-line-item"
    data-line-index="<?= Html::encode((string) $lineIndex) ?>"
>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0" data-role="song-line-title">Строка</h3>
    </div>

    <?= Html::activeHiddenInput($lineModel, $lineIdAttribute) ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-0">
                <?= Html::activeLabel($lineModel, $lineTextAttribute, ['class' => 'form-label']) ?>
                <?= Html::activeTextarea($lineModel, $lineTextAttribute, [
                        'class' => $lineInputClass,
                        'rows' => 2,
                        'data-role' => 'song-line-original-text',
                        'spellcheck' => 'false',
                ]) ?>
                <?php if ($lineModel->hasErrors('original_text')): ?>
                    <div class="invalid-feedback d-block"><?= Html::encode($lineModel->getFirstError('original_text')) ?></div>
                <?php endif; ?>
            </div>

        </div>
        <?php foreach ($translationModels as $languageId => $translationModel): ?>
            <?php
            $translationIndex = $translationIndexes[$languageId];
            $translationIdAttribute = '[' . $translationIndex . ']id';
            $translationLanguageIdAttribute = '[' . $translationIndex . ']language_id';
            $translationTextAttribute = '[' . $translationIndex . ']translated_text';
            $translationInputClass = 'form-control' . ($translationModel->hasErrors('translated_text') ? ' is-invalid' : '');
            ?>
            <div class="col-md-6">
                <?= Html::activeHiddenInput($translationModel, $translationIdAttribute) ?>
                <?= Html::activeHiddenInput($translationModel, $translationLanguageIdAttribute) ?>

                <div class="mb-0">
                    <?= Html::activeLabel($translationModel, $translationTextAttribute, [
                        'class' => 'form-label',
                        'label' => 'Перевод: ' . ($languageLabels[$languageId] ?? (string) $languageId),
                    ]) ?>
                    <?= Html::activeTextarea($translationModel, $translationTextAttribute, [
                        'class' => $translationInputClass,
                        'rows' => 2,
                        'data-role' => 'song-line-translation-text',
                    ]) ?>
                    <?php if ($translationModel->hasErrors('translated_text')): ?>
                        <div class="invalid-feedback d-block"><?= Html::encode($translationModel->getFirstError('translated_text')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
