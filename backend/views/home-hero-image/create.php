<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\HomeHeroImageForm $formModel */
/** @var array $artistItems */

$this->title = 'Новое hero-изображение';
$this->params['breadcrumbs'][] = ['label' => 'Hero главной', 'url' => ['/home-hero-image/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="home-hero-image-create">
    <h1><?= $this->title ?></h1>

    <?= $this->render('_form', [
        'formModel' => $formModel,
        'artistItems' => $artistItems,
    ]) ?>
</div>
