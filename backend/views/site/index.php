<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'GeoLyrics Admin';
?>
<div class="site-index">
    <div class="jumbotron text-center bg-transparent">
        <h1 class="display-5">GeoLyrics Admin</h1>
        <p class="lead">Бэкофис каталога песен, переводов, исполнителей и записей.</p>
    </div>

    <div class="body-content">
        <div class="row">
            <div class="col-lg-4">
                <h2>Справочники</h2>
                <p>Языки и исполнители для всей доменной модели.</p>
                <p><?= Html::a('Открыть языки', ['/language/index'], ['class' => 'btn btn-outline-primary']) ?></p>
                <p><?= Html::a('Открыть исполнителей', ['/artist/index'], ['class' => 'btn btn-outline-primary']) ?></p>
            </div>
            <div class="col-lg-4">
                <h2>Каталог</h2>
                <p>Песни с оригинальным языком, переводами и будущими строками текста.</p>
                <p><?= Html::a('Открыть песни', ['/song/index'], ['class' => 'btn btn-outline-primary']) ?></p>
            </div>
            <div class="col-lg-4">
                <h2>Медиа и релизы</h2>
                <p>Записи хранят связь песни, типа записи, аккордов и описания.</p>
                <p><?= Html::a('Открыть записи', ['/recording/index'], ['class' => 'btn btn-outline-primary']) ?></p>
            </div>
        </div>
    </div>
</div>
