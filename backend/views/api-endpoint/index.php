<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $apiUrl */
/** @var yii\data\ArrayDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'API эндпоинты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="api-endpoint-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <?php if ($apiUrl !== ''): ?>
                <div class="text-body-secondary">
                    Базовый URL: <code><?= Html::encode($apiUrl) ?></code>
                </div>
            <?php endif; ?>
        </div>
        <span class="badge text-bg-secondary">
            Всего: <?= Html::encode((string) $dataProvider->getTotalCount()) ?>
        </span>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => false,
        'columns' => [
            [
                'attribute' => 'method',
                'label' => 'Метод',
                'format' => 'raw',
                'value' => static function (array $row): string {
                    return Html::tag('span', Html::encode($row['method']), ['class' => 'badge text-bg-primary']);
                },
            ],
            [
                'attribute' => 'path',
                'label' => 'URL',
                'format' => 'raw',
                'value' => static function (array $row): string {
                    return Html::tag('code', Html::encode($row['path']));
                },
            ],
            [
                'attribute' => 'route',
                'label' => 'Route',
                'format' => 'raw',
                'value' => static function (array $row): string {
                    return Html::tag('code', Html::encode($row['route']));
                },
            ],
        ],
    ]) ?>
</div>
