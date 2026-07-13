<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Genre $model */
/** @var array $publicationStatusItems */
/** @var common\models\GenreTranslation[] $translationModels */
/** @var array $languageLabels */

$this->title = 'Редактировать жанр: ' . $model->default_name;
$this->params['breadcrumbs'][] = ['label' => 'Жанры', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="genre-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'publicationStatusItems' => $publicationStatusItems,
        'translationModels' => $translationModels,
        'languageLabels' => $languageLabels,
    ]) ?>
</div>
