<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class ArtistGalleryAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/artist-gallery.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
