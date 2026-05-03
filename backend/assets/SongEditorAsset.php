<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class SongEditorAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/song-editor.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
