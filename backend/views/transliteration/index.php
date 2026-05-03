<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\GeorgianTransliterationForm $formModel */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Транслитерация';
$this->params['breadcrumbs'][] = $this->title;
$languages = $formModel->getLanguages();
?>
<div class="transliteration-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Английская колонка используется для автоматической генерации slug у песен и записей.</p>
        </div>
    </div>

    <?php if ($languages === []): ?>
        <div class="alert alert-warning mb-0">Нет доступных целевых языков для транслитерации.</div>
    <?php else: ?>
        <?php $form = ActiveForm::begin(); ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Грузинская буква</th>
                        <?php foreach ($languages as $language): ?>
                            <th><?= Html::encode($language->native_name . ' (' . $language->code . ')') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formModel->getSourceLetters() as $sourceChar): ?>
                        <tr>
                            <th><?= Html::encode($sourceChar) ?></th>
                            <?php foreach ($languages as $language): ?>
                                <td>
                                    <?= Html::textInput(
                                        $formModel->formName() . '[matrix][' . $sourceChar . '][' . $language->id . ']',
                                        $formModel->getValue($sourceChar, $language->id),
                                        [
                                            'class' => 'form-control',
                                            'maxlength' => 32,
                                            'spellcheck' => 'false',
                                        ],
                                    ) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-group mt-3">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    <?php endif; ?>
</div>
