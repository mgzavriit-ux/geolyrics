<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\SongEditorForm $formModel */

$this->title = 'Редактирование песни: ' . $formModel->getSong()->default_title;
$this->params['breadcrumbs'][] = ['label' => 'Песни', 'url' => ['/song/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="song-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'formModel' => $formModel,
    ]) ?>
</div>
