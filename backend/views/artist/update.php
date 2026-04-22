<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Artist $model */
/** @var array $publicationStatusItems */
/** @var array $typeItems */
/** @var common\models\ArtistTranslation[] $translationModels */
/** @var array $languageLabels */

$this->title = 'Редактирование исполнителя: ' . $model->default_name;
$this->params['breadcrumbs'][] = ['label' => 'Исполнители', 'url' => ['/artist/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="artist-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'publicationStatusItems' => $publicationStatusItems,
        'typeItems' => $typeItems,
        'translationModels' => $translationModels,
        'languageLabels' => $languageLabels,
    ]) ?>
</div>
