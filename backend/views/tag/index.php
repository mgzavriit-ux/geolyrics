<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\TagSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $publicationStatusItems */

use common\models\Tag;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Теги';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tag-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить тег', ['/tag/create'], ['class' => 'btn btn-success']) ?>
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
            'slug',
            'default_name',
            [
                'attribute' => 'publication_status',
                'filter' => $publicationStatusItems,
                'value' => static function (Tag $model): string {
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
                'urlCreator' => static function (string $action, Tag $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
