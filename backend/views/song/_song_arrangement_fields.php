<?php

declare(strict_types=1);

/** @var yii\bootstrap5\ActiveForm $form */
/** @var int|string $arrangementIndex */
/** @var common\models\SongArrangement $arrangementModel */
/** @var array<string, string> $formatItems */
/** @var bool $isHidden */

use yii\helpers\Html;

$itemClass = 'song-arrangement-item border rounded p-3 mb-4';

if ($isHidden) {
    $itemClass .= ' d-none';
}
?>
<div
    class="<?= Html::encode($itemClass) ?>"
    data-role="song-arrangement-item"
    data-arrangement-index="<?= Html::encode((string) $arrangementIndex) ?>"
>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0" data-role="song-arrangement-title">Аранжировка</h3>
        <?= Html::button('Удалить аранжировку', [
            'class' => 'btn btn-outline-danger btn-sm',
            'type' => 'button',
            'data-role' => 'remove-song-arrangement',
        ]) ?>
    </div>

    <?= Html::activeHiddenInput($arrangementModel, '[' . $arrangementIndex . ']id') ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <?= $form->field($arrangementModel, '[' . $arrangementIndex . ']title')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6 col-lg-3">
            <?= $form->field($arrangementModel, '[' . $arrangementIndex . ']source_format')->dropDownList($formatItems) ?>
        </div>
        <div class="col-md-6 col-lg-3">
            <?= $form->field($arrangementModel, '[' . $arrangementIndex . ']original_key')->textInput([
                'maxlength' => true,
                'placeholder' => 'Am, C, F#',
            ]) ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg-3">
            <?= $form->field($arrangementModel, '[' . $arrangementIndex . ']capo')->input('number', [
                'min' => 0,
                'max' => 24,
            ]) ?>
        </div>
    </div>

    <?= $form->field($arrangementModel, '[' . $arrangementIndex . ']source_text')->textarea([
        'rows' => 10,
        'placeholder' => "[Am]როცა მთვარე [F]ვარსკვლავებს [C]ნახავს,\n[F]Следующая строка\n\n{title: Example}",
    ]) ?>

    <div class="form-text">
        Для `ChordPro` используй аккорды в квадратных скобках прямо перед фрагментом текста. Для legacy-данных можно выбрать формат `Простой текст`.
    </div>
</div>
