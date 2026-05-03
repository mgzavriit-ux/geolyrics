<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int $languageId */
/** @var string $languageLabel */
/** @var int $translationIndex */
/** @var common\models\SongTranslation $translationModel */
/** @var bool $isVisible */

use yii\helpers\Html;

$titleAttribute = '[' . $translationIndex . ']title';
$subtitleAttribute = '[' . $translationIndex . ']subtitle';
$descriptionAttribute = '[' . $translationIndex . ']description';
$historyAttribute = '[' . $translationIndex . ']history';

$titleInputClass = 'form-control' . ($translationModel->hasErrors('title') ? ' is-invalid' : '');
$subtitleInputClass = 'form-control' . ($translationModel->hasErrors('subtitle') ? ' is-invalid' : '');
$descriptionInputClass = 'form-control' . ($translationModel->hasErrors('description') ? ' is-invalid' : '');
$historyInputClass = 'form-control' . ($translationModel->hasErrors('history') ? ' is-invalid' : '');
$panelClass = 'song-translation-panel h-100';

if ($isVisible === false) {
    $panelClass .= ' d-none';
}
?>
<div class="<?= Html::encode($panelClass) ?>" data-role="translation-panel" data-language-id="<?= Html::encode((string) $languageId) ?>">
    <div class="d-flex flex-column h-100">

        <?= Html::activeHiddenInput($translationModel, '[' . $translationIndex . ']id') ?>
        <?= Html::activeHiddenInput($translationModel, '[' . $translationIndex . ']language_id') ?>

        <div class="mb-3">
            <?= Html::activeLabel($translationModel, $titleAttribute, ['class' => 'form-label']) ?>
            <?= Html::activeTextInput($translationModel, $titleAttribute, [
                'class' => $titleInputClass,
                'maxlength' => true,
            ]) ?>
            <?php if ($translationModel->hasErrors('title')): ?>
                <div class="invalid-feedback d-block"><?= Html::encode($translationModel->getFirstError('title')) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <?= Html::activeLabel($translationModel, $subtitleAttribute, ['class' => 'form-label']) ?>
            <?= Html::activeTextInput($translationModel, $subtitleAttribute, [
                'class' => $subtitleInputClass,
                'maxlength' => true,
            ]) ?>
            <?php if ($translationModel->hasErrors('subtitle')): ?>
                <div class="invalid-feedback d-block"><?= Html::encode($translationModel->getFirstError('subtitle')) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <?= Html::activeLabel($translationModel, $descriptionAttribute, ['class' => 'form-label']) ?>
            <?= Html::activeTextarea($translationModel, $descriptionAttribute, [
                'class' => $descriptionInputClass,
                'rows' => 3,
            ]) ?>
            <?php if ($translationModel->hasErrors('description')): ?>
                <div class="invalid-feedback d-block"><?= Html::encode($translationModel->getFirstError('description')) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-0">
            <?= Html::activeLabel($translationModel, $historyAttribute, ['class' => 'form-label']) ?>
            <?= Html::activeTextarea($translationModel, $historyAttribute, [
                'class' => $historyInputClass,
                'rows' => 5,
            ]) ?>
            <?php if ($translationModel->hasErrors('history')): ?>
                <div class="invalid-feedback d-block"><?= Html::encode($translationModel->getFirstError('history')) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
