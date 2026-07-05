<?php

declare(strict_types=1);

namespace backend\assets;

use yii\web\AssetBundle;

final class BootstrapIconsAsset extends AssetBundle
{
    public $sourcePath = '@vendor/twbs/bootstrap-icons/font';
    public $css = [
        'bootstrap-icons.min.css',
    ];
}
