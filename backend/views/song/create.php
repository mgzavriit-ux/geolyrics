<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Song $model */
/** @var array $languageItems */
/** @var array $publicationStatusItems */

$this->title = 'Новая песня';
$this->params['breadcrumbs'][] = ['label' => 'Песни', 'url' => ['/song/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="song-create">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'languageItems' => $languageItems,
        'publicationStatusItems' => $publicationStatusItems,
    ]) ?>
</div>
