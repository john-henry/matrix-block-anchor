<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Matrix Block Anchor CP asset bundle
 *
 * Registers the plugin's control panel CSS and JavaScript, depending on Craft's
 * core CP asset bundle so scripts load in the correct order.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class MatrixBlockAnchorAssets extends AssetBundle
{
    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Configures source path, dependencies, and asset file lists.
     *
     * @return void
     * @throws \yii\base\InvalidConfigException If the asset bundle is misconfigured
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function init(): void
    {
        $this->sourcePath = '@johnhenry/matrixblockanchor/resources';
        $this->depends = [CpAsset::class];

        $this->css = [
            'css/cp.css',
        ];

        $this->js = [
            'js/cp.js',
        ];

        parent::init();
    }
}
