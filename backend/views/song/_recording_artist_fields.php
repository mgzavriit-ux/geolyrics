<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var int|string $artistFlatIndex */
/** @var int|string $recordingIndex */
/** @var common\models\RecordingArtist $artistModel */
/** @var array<int, string> $artistItems */
/** @var array<string, string> $roleItems */
/** @var bool $isHidden */

use yii\helpers\Html;

$itemClass = 'recording-artist-item row g-3 mb-2';

if ($isHidden) {
    $itemClass .= ' d-none';
}
?>
<div
    class="<?= Html::encode($itemClass) ?>"
    data-role="recording-artist-item"
    data-artist-flat-index="<?= Html::encode((string) $artistFlatIndex) ?>"
>
    <?= Html::hiddenInput(
        'recordingArtistRecordingIndexes[' . $artistFlatIndex . ']',
        $recordingIndex,
        ['data-role' => 'recording-artist-recording-index'],
    ) ?>
    <div class="col-md-6">
        <?= Html::activeHiddenInput($artistModel, '[' . $artistFlatIndex . ']recording_id') ?>
        <?= $form->field($artistModel, '[' . $artistFlatIndex . ']artist_id')->dropDownList(
            $artistItems,
            ['prompt' => 'Выберите исполнителя'],
        ) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($artistModel, '[' . $artistFlatIndex . ']role')->dropDownList(
            $roleItems,
            ['prompt' => 'Выберите роль'],
        ) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($artistModel, '[' . $artistFlatIndex . ']sort_order')->input('number') ?>
    </div>
</div>
