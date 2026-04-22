<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\RecordingSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $publicationStatusItems */
/** @var array $recordingTypeItems */
/** @var array $songItems */

use common\models\Recording;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Записи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="recording-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить запись', ['/recording/create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'default_title',
            'slug',
            [
                'attribute' => 'song_id',
                'filter' => $songItems,
                'value' => static function (Recording $model): string {
                    if ($model->song === null) {
                        return '';
                    }

                    return $model->song->default_title;
                },
            ],
            [
                'attribute' => 'recording_type',
                'filter' => $recordingTypeItems,
                'value' => static function (Recording $model): string {
                    $items = $model->getRecordingTypeList();

                    return $items[$model->recording_type] ?? $model->recording_type;
                },
            ],
            [
                'attribute' => 'publication_status',
                'filter' => $publicationStatusItems,
                'value' => static function (Recording $model): string {
                    $items = $model->getPublicationStatusList();

                    return $items[$model->publication_status] ?? $model->publication_status;
                },
            ],
            'release_year',
            [
                'attribute' => 'updated_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static function (string $action, Recording $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
