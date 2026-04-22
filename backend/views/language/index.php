<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\LanguageSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Language;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Языки';
$this->params['breadcrumbs'][] = $this->title;
$booleanItems = [
    1 => 'Да',
    0 => 'Нет',
];
?>
<div class="language-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= $this->title ?></h1>
        <?= Html::a('Добавить язык', ['/language/create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'code',
            'locale',
            'name',
            'native_name',
            [
                'attribute' => 'is_active',
                'filter' => $booleanItems,
                'format' => 'boolean',
            ],
            [
                'attribute' => 'is_default',
                'filter' => $booleanItems,
                'format' => 'boolean',
            ],
            'sort_order',
            [
                'attribute' => 'updated_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static function (string $action, Language $model): array {
                    return [$action, 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
