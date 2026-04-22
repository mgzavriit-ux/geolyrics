<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Recording $model */
/** @var array $publicationStatusItems */
/** @var array $recordingTypeItems */
/** @var array $songItems */

$this->title = 'Редактирование записи: ' . $model->default_title;
$this->params['breadcrumbs'][] = ['label' => 'Записи', 'url' => ['/recording/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="recording-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'publicationStatusItems' => $publicationStatusItems,
        'recordingTypeItems' => $recordingTypeItems,
        'songItems' => $songItems,
    ]) ?>
</div>
