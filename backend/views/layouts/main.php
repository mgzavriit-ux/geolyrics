<?php

/** @var View $this */
/** @var string $content */

use backend\assets\AppAsset;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\web\View;

AppAsset::register($this);

$controllerId = Yii::$app->controller->id;
$menuItems = [
    [
        'label' => 'Главная',
        'url' => ['/site/index'],
        'icon' => 'bi-speedometer2',
        'active' => $controllerId === 'site',
    ],
    [
        'header' => 'Каталог',
    ],
    [
        'label' => 'Языки',
        'url' => ['/language/index'],
        'icon' => 'bi-translate',
        'active' => $controllerId === 'language',
    ],
    [
        'label' => 'Транслитерация',
        'url' => ['/transliteration/index'],
        'icon' => 'bi-arrow-left-right',
        'active' => $controllerId === 'transliteration',
    ],
    [
        'label' => 'Исполнители',
        'url' => ['/artist/index'],
        'icon' => 'bi-person-lines-fill',
        'active' => $controllerId === 'artist',
    ],
    [
        'label' => 'Песни',
        'url' => ['/song/index'],
        'icon' => 'bi-music-note-list',
        'active' => $controllerId === 'song',
    ],
    [
        'label' => 'Записи',
        'url' => ['/recording/index'],
        'icon' => 'bi-disc',
        'active' => $controllerId === 'recording',
    ],
];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<?php $this->beginBody() ?>

<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Переключить меню">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <?= Html::a(
                        '<i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i> Панель',
                        ['/site/index'],
                        ['class' => 'nav-link'],
                    ) ?>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (Yii::$app->user->isGuest): ?>
                    <li class="nav-item">
                        <?= Html::a(
                            '<i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Вход',
                            ['/site/login'],
                            ['class' => 'nav-link'],
                        ) ?>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                            <?= Html::encode(Yii::$app->user->identity->username) ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <span class="dropdown-header"><?= Html::encode(Yii::$app->user->identity->username) ?></span>
                            <div class="dropdown-divider"></div>
                            <?= Html::beginForm(['/site/logout'], 'post') ?>
                                <?= Html::submitButton(
                                    '<i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Выход',
                                    ['class' => 'dropdown-item text-danger'],
                                ) ?>
                            <?= Html::endForm() ?>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <?= Html::a(
                '<span class="brand-text fw-semibold">GeoLyrics Admin</span>',
                Yii::$app->homeUrl,
                ['class' => 'brand-link'],
            ) ?>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                    <?php foreach ($menuItems as $menuItem): ?>
                        <?php if (isset($menuItem['header'])): ?>
                            <li class="nav-header"><?= Html::encode($menuItem['header']) ?></li>
                            <?php continue; ?>
                        <?php endif; ?>
                        <li class="nav-item">
                            <?= Html::a(
                                '<i class="nav-icon bi ' . Html::encode($menuItem['icon']) . '" aria-hidden="true"></i>'
                                    . '<p>' . Html::encode($menuItem['label']) . '</p>',
                                $menuItem['url'],
                                ['class' => ['nav-link', $menuItem['active'] ? 'active' : '']],
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <?php if (empty($this->params['breadcrumbs']) === false): ?>
            <div class="app-content-header">
                <div class="container-fluid">
                    <?= Breadcrumbs::widget([
                        'links' => $this->params['breadcrumbs'],
                        'options' => ['class' => 'breadcrumb mb-0'],
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="app-content">
            <div class="container-fluid">
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>GeoLyrics Admin</strong>
        <div class="float-end d-none d-sm-inline">Каталог песен и исполнителей</div>
    </footer>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
