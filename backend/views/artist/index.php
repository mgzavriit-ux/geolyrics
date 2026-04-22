<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\ArtistSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $publicationStatusItems */
/** @var array $typeItems */

use common\models\Artist;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Исполнители';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="artist-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить исполнителя', ['/artist/create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'default_name',
            'slug',
            [
                'attribute' => 'type',
                'filter' => $typeItems,
                'value' => static function (Artist $model): string {
                    $items = $model->getTypeList();

                    return $items[$model->type] ?? $model->type;
                },
            ],
            [
                'attribute' => 'publication_status',
                'filter' => $publicationStatusItems,
                'value' => static function (Artist $model): string {
                    $items = $model->getPublicationStatusList();

                    return $items[$model->publication_status] ?? $model->publication_status;
                },
            ],
            [
                'attribute' => 'updated_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static function (string $action, Artist $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
