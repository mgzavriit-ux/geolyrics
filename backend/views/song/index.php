<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\SongSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $languageItems */
/** @var array $publicationStatusItems */

use common\models\Song;
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
        'columns' => [
            'id',
            'default_title',
            'slug',
            [
                'attribute' => 'original_language_id',
                'filter' => $languageItems,
                'value' => static function (Song $model): string {
                    if ($model->originalLanguage === null) {
                        return '';
                    }

                    return $model->originalLanguage->name;
                },
            ],
            [
                'attribute' => 'publication_status',
                'filter' => $publicationStatusItems,
                'value' => static function (Song $model): string {
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
                'urlCreator' => static function (string $action, Song $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
