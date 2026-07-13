<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Tag $model */
/** @var array $publicationStatusItems */
/** @var common\models\TagTranslation[] $translationModels */
/** @var array $languageLabels */

$this->title = 'Редактировать тег: ' . $model->default_name;
$this->params['breadcrumbs'][] = ['label' => 'Теги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tag-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'publicationStatusItems' => $publicationStatusItems,
        'translationModels' => $translationModels,
        'languageLabels' => $languageLabels,
    ]) ?>
</div>
