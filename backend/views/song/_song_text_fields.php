<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int $languageId */
/** @var string $languageLabel */
/** @var bool $isVisible */
/** @var string $textValue */

use yii\helpers\Html;

$panelClass = 'song-text-panel';

if ($isVisible === false) {
    $panelClass .= ' d-none';
}
?>
<div class="<?= Html::encode($panelClass) ?>" data-role="song-text-panel" data-language-id="<?= Html::encode((string) $languageId) ?>">
    <?= Html::hiddenInput('songFullText[' . $languageId . '][dirty]', '0', [
        'data-role' => 'song-text-dirty',
    ]) ?>

    <div class="mb-0">
        <?= Html::label('Текст: ' . $languageLabel, 'song-full-text-' . $languageId, ['class' => 'form-label']) ?>
        <?= Html::textarea('songFullText[' . $languageId . '][text]', $textValue, [
            'id' => 'song-full-text-' . $languageId,
            'class' => 'form-control',
            'rows' => 10,
            'spellcheck' => 'false',
            'data-role' => 'song-text-source',
            'data-language-id' => (string) $languageId,
        ]) ?>
    </div>
</div>
