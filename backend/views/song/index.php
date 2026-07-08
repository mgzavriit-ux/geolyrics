<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\SongSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $artistItems */
/** @var array $languageItems */

/** @var array $publicationStatusItems */

use common\models\Song;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Песни';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="song-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить песню', ['/song/create'], ['class' => 'btn btn-success']) ?>
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
                    'default_title',
                    [
                            'attribute' => 'artist_id',
                            'label' => 'Исполнители',
                            'filter' => $artistItems,
                            'value' => static function (Song $model): string {
                                return implode(', ', $model->getRecordingPerformerNames());
                            },
                    ],
                    'slug',
                    [
                            'attribute' => 'publication_status',
                            'filter' => $publicationStatusItems,
                            'value' => static function (Song $model): string {
                                $items = $model->getPublicationStatusList();

                                return $items[$model->publication_status] ?? $model->publication_status;
                            },
                    ],
                    [
                            'attribute' => 'published_at',
                            'label' => 'Опубликована',
                            'format' => 'datetime',
                            'filter' => false,
                    ],
                    [
                            'class' => ActionColumn::class,
                            'urlCreator' => static function (string $action, Song $model): array {
                                return [$action, 'id' => $model->id];
                            },
                    ],
            ],
    ]) ?>
</div>
