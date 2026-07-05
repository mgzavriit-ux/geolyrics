<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'GeoLyrics Admin';

$items = [
    [
        'label' => 'Языки',
        'description' => 'Справочник локалей и языков песен.',
        'url' => ['/language/index'],
        'icon' => 'bi-translate',
        'theme' => 'text-bg-primary',
    ],
    [
        'label' => 'Транслитерация',
        'description' => 'Правила грузинской транслитерации.',
        'url' => ['/transliteration/index'],
        'icon' => 'bi-arrow-left-right',
        'theme' => 'text-bg-info',
    ],
    [
        'label' => 'Исполнители',
        'description' => 'Артисты, переводы имен и галереи.',
        'url' => ['/artist/index'],
        'icon' => 'bi-person-lines-fill',
        'theme' => 'text-bg-success',
    ],
    [
        'label' => 'Песни',
        'description' => 'Тексты, переводы, строки и языки.',
        'url' => ['/song/index'],
        'icon' => 'bi-music-note-list',
        'theme' => 'text-bg-warning',
    ],
    [
        'label' => 'Записи',
        'description' => 'Релизы, медиа, аккорды и участники.',
        'url' => ['/recording/index'],
        'icon' => 'bi-disc',
        'theme' => 'text-bg-danger',
    ],
];
?>
<div class="site-index">
    <div class="mb-4">
        <h1 class="h3 mb-1">GeoLyrics Admin</h1>
        <p class="text-secondary mb-0">Бэкофис каталога песен, переводов, исполнителей и записей.</p>
    </div>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <?= Html::a(
                    '<span class="info-box-icon ' . Html::encode($item['theme']) . ' shadow-sm">'
                        . '<i class="bi ' . Html::encode($item['icon']) . '" aria-hidden="true"></i>'
                        . '</span>'
                        . '<div class="info-box-content">'
                        . '<span class="info-box-text">' . Html::encode($item['label']) . '</span>'
                        . '<span class="info-box-number fs-6 fw-normal">' . Html::encode($item['description']) . '</span>'
                        . '</div>'
                        . '<span class="backend-dashboard-arrow text-secondary">'
                        . '<i class="bi bi-chevron-right" aria-hidden="true"></i>'
                        . '</span>',
                    $item['url'],
                    ['class' => 'info-box backend-dashboard-link'],
                ) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
