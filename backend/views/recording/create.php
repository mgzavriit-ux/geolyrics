<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Recording $model */
/** @var backend\models\RecordingMediaUploadForm $uploadForm */
/** @var array $publicationStatusItems */
/** @var array $recordingTypeItems */
/** @var array $songItems */

$this->title = 'Новая запись';
$this->params['breadcrumbs'][] = ['label' => 'Записи', 'url' => ['/recording/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="recording-create">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'uploadForm' => $uploadForm,
        'publicationStatusItems' => $publicationStatusItems,
        'recordingTypeItems' => $recordingTypeItems,
        'songItems' => $songItems,
    ]) ?>
</div>
