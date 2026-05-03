<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class RecordingMediaAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/recording-media.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
