<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor;

use Craft;
use craft\base\Plugin as BasePlugin;

use craft\events\RegisterComponentTypesEvent;
use craft\fields\Matrix;
use craft\services\Fields;
use craft\web\View;

use johnhenry\matrixblockanchor\assetbundles\MatrixBlockAnchorAssets;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\models\Settings;

use yii\base\Event;

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
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the plugin has a CP section
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public bool $hasCpSection = false;

    /**
     * @var bool Whether the plugin has CP settings
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public bool $hasCpSettings = true;

    /**
     * @var string The plugin schema version
     * @author John Henry Donovan <info@johnhenry.ie>
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
        $this->_registerAssets();
    }

    // =========================================================================
    // Protected Methods
    // =========================================================================

    /**
     * Creates the settings model for this plugin.
     *
     * @return Settings The settings model instance
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function createSettingsModel(): models\Settings
    {
        return new Settings();
    }

    /**
     * Renders the settings page HTML.
     *
     * @return string The rendered settings template
     * @throws \Twig\Error\LoaderError If the template cannot be loaded
     * @throws \Twig\Error\RuntimeError If there is a runtime error in the template
     * @throws \Twig\Error\SyntaxError If there is a syntax error in the template
     * @throws \yii\base\Exception If the view cannot render the template
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'matrix-block-anchor/settings',
            [
                'settings' => $this->getSettings(),
                'config' => array_filter(
                    Craft::$app->getConfig()->getConfigFromFile('matrix-block-anchor'),
                    fn($value) => $value !== null
                ),
                'fieldUsage' => $this->_getFieldUsage(),
            ]
        );
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Registers the MatrixBlockAnchorField field type with Craft.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerField(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = MatrixBlockAnchorField::class;
            });
    }

    /**
     * Registers the CP asset bundle on every CP template render.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerAssets(): void
    {
        if (Craft::$app instanceof \craft\console\Application) {
            return;
        }

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function() {
                if (Craft::$app->getRequest()->getIsCpRequest()) {
                    $view = Craft::$app->getView();
                    $view->registerAssetBundle(MatrixBlockAnchorAssets::class);
                }
            }
        );
    }

    /**
     * Builds a list of all anchor field usages across Matrix fields and entry types.
     *
     * Iterates all MatrixBlockAnchorField instances, resolves the field layouts they
     * belong to, and maps each back to its owning Matrix field and entry type for
     * display on the settings page.
     *
     * @return array<int, array{anchorField: MatrixBlockAnchorField, matrixField: Matrix, entryType: \craft\models\EntryType, matrixFieldUrl: string|null, entryTypeUrl: string|null}> Usage map entries
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _getFieldUsage(): array
    {
        $usage = [];
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        // Find all MatrixBlockAnchorField instances
        $anchorFields = $fieldsService->getFieldsByType(MatrixBlockAnchorField::class);

        foreach ($anchorFields as $anchorField) {
            assert($anchorField instanceof MatrixBlockAnchorField);
            // Get the field layouts that use this field
            $layouts = $fieldsService->findFieldUsages($anchorField);

            foreach ($layouts as $layout) {
                // Try to find the entry type that owns this layout
                $entryType = null;

                // Search through all entry types to find one with matching field layout
                foreach ($entriesService->getAllEntryTypes() as $et) {
                    if ($et->getFieldLayout()->id === $layout->id) {
                        $entryType = $et;
                        break;
                    }
                }

                if ($entryType) {
                    // Find which Matrix field uses this entry type
                    $matrixFields = $fieldsService->getFieldsByType(Matrix::class);

                    foreach ($matrixFields as $matrixField) {
                        assert($matrixField instanceof Matrix);
                        $settings = $matrixField->settings;
                        if (isset($settings['entryTypes'])) {
                            foreach ($settings['entryTypes'] as $entryTypeConfig) {
                                if (isset($entryTypeConfig['uid']) && $entryTypeConfig['uid'] === $entryType->uid) {
                                    $usage[] = [
                                        'anchorField' => $anchorField,
                                        'matrixField' => $matrixField,
                                        'entryType' => $entryType,
                                        'matrixFieldUrl' => $matrixField->getCpEditUrl(),
                                        'entryTypeUrl' => $entryType->getCpEditUrl(),
                                    ];
                                    break 2; // Break out of both loops once we find a match
                                }
                            }
                        }
                    }
                }
            }
        }

        return $usage;
    }
}
