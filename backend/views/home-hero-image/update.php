<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\HomeHeroImageForm $formModel */
/** @var array $artistItems */

$homeHeroImage = $formModel->getHomeHeroImage();
$artistName = $homeHeroImage->artist === null ? (string) $homeHeroImage->id : $homeHeroImage->artist->default_name;

$this->title = 'Редактирование hero-изображения: ' . $artistName;
$this->params['breadcrumbs'][] = ['label' => 'Hero главной', 'url' => ['/home-hero-image/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="home-hero-image-update">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'formModel' => $formModel,
        'artistItems' => $artistItems,
    ]) ?>
</div>
