<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\HomeHeroImageSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $artistItems */
/** @var common\components\storage\StorageInterface $storage */

use common\models\HomeHeroImage;
use common\models\MediaAsset;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Hero главной';
$this->params['breadcrumbs'][] = $this->title;
$booleanItems = [
    1 => 'Да',
    0 => 'Нет',
];
?>
<div class="home-hero-image-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить hero-изображение', ['/home-hero-image/create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => [
            'class' => LinkPager::class,
            'options' => [
                'class' => 'pagination justify-content-center align-items-center backend-pager mt-4 mb-0',
            ],
            'maxButtonCount' => 7,
            'firstPageLabel' => '«',
            'lastPageLabel' => '»',
            'prevPageLabel' => '‹',
            'nextPageLabel' => '›',
        ],
        'columns' => [
            'id',
            [
                'label' => 'Фото',
                'format' => 'raw',
                'value' => static function (HomeHeroImage $model) use ($storage): string {
                    $mediaAsset = $model->mediaAsset;

                    if ($mediaAsset instanceof MediaAsset === false) {
                        return '';
                    }

                    return Html::img($storage->getPublicUrl($mediaAsset->path), [
                        'class' => 'home-hero-image-grid-preview',
                        'alt' => $model->artist === null ? '' : $model->artist->default_name,
                    ]);
                },
            ],
            [
                'attribute' => 'artist_id',
                'filter' => $artistItems,
                'value' => static function (HomeHeroImage $model): string {
                    if ($model->artist === null) {
                        return '';
                    }

                    return $model->artist->default_name;
                },
            ],
            [
                'attribute' => 'is_active',
                'filter' => $booleanItems,
                'format' => 'boolean',
            ],
            'sort_order',
            [
                'label' => 'Focal point',
                'value' => static function (HomeHeroImage $model): string {
                    return $model->focal_point_x . '% / ' . $model->focal_point_y . '%';
                },
            ],
            [
                'attribute' => 'updated_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static function (string $action, HomeHeroImage $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
