<?php

/** @var yii\web\View $this */

$this->title = 'GeoLyrics Admin';
?>
<div class="site-index">
    <div class="jumbotron text-center bg-transparent">
        <h1 class="display-5">GeoLyrics Admin</h1>
        <p class="lead">Бэкофис для каталога песен, медиа, переводов и будущих AI-процессов.</p>
    </div>

    <div class="body-content">
        <div class="row">
            <div class="col-lg-4">
                <h2>Инфраструктура</h2>
                <p>Yii2, PostgreSQL, Redis, yii2-queue, docker-compose.</p>
            </div>
            <div class="col-lg-4">
                <h2>Файлы</h2>
                <p>Сейчас используется локальное хранилище через компонент <code>storage</code> с будущей заменой на S3-compatible.</p>
            </div>
            <div class="col-lg-4">
                <h2>API</h2>
                <p>Публичный API стартует с <code>/health</code> и <code>/v1/health</code>, дальше сюда ляжет каталог песен и переводов.</p>
            </div>
        </div>
    </div>
</div>
