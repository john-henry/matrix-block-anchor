<?php

namespace johnhenry\matrixblockanchor\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MatrixBlockAnchorAssets extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@johnhenry/matrixblockanchor/assetbundles/dist';
        $this->depends = [CpAsset::class];

        $this->css = [
            'css/matrix-block-anchor.css',
        ];

        $this->js = [
            'js/matrix-block-anchor.js',
        ];

        parent::init();
    }
}