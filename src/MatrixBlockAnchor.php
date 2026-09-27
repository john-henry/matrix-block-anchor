<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor;

use craft\base\Plugin as BasePlugin;

use johnhenry\matrixblockanchor\base\PluginTrait;
use johnhenry\matrixblockanchor\models\Settings;

/**
 * Matrix Block Anchor plugin
 *
 * Provides a custom field type for adding unique, stable anchor IDs to Craft
 * matrix blocks, enabling deep-linking to individual blocks on a page.
 *
 * @method static MatrixBlockAnchor getInstance()
 * @method Settings getSettings()
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class MatrixBlockAnchor extends BasePlugin
{
    // =========================================================================
    // Traits
    // =========================================================================

    use PluginTrait;

    // =========================================================================
    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether the plugin has a CP section
     * @since 1.0.0
     */
    public bool $hasCpSection = false;

    /**
     * @var bool Whether the plugin has CP settings
     * @since 1.0.0
     */
    public bool $hasCpSettings = true;

    /**
     * @var string The plugin schema version
     * @since 1.0.0
     */
    public string $schemaVersion = '1.0.0';

    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Initialises the plugin, registering the field type and CP assets.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function init(): void
    {
        parent::init();

        $this->_registerField();
        $this->_registerAnchorCacheReset();
        $this->_registerAssets();
    }
}
