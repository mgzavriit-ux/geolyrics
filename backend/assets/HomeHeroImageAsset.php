<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class HomeHeroImageAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/home-hero-image.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
