<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Language $model */

$this->title = 'Редактирование языка: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Языки', 'url' => ['/language/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="language-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
