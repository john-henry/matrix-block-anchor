<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\base;

use Craft;
use craft\console\Application as ConsoleApplication;

use craft\events\RegisterComponentTypesEvent;
use craft\fields\Matrix;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\View;

use johnhenry\matrixblockanchor\assetbundles\MatrixBlockAnchorAssets;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\models\Settings;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;

/**
 * PluginTrait
 *
 * Wires the plugin's field type and control panel assets, and owns its settings
 * lifecycle, including the usage map the settings screen shows. Keeps the main
 * plugin class a thin shell.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 3.4.0
 */
trait PluginTrait
{
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
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Renders the settings page HTML.
     *
     * @return string The rendered settings template
     * @throws LoaderError If the template cannot be loaded
     * @throws RuntimeError If there is a runtime error in the template
     * @throws SyntaxError If there is a syntax error in the template
     * @throws \yii\base\Exception If the view cannot render the template
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'matrix-block-anchor/_settings',
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
     * Clears the anchors cached for uniqueness checks whenever an element is
     * saved or deleted, so later checks in the same request see the change.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _registerAnchorCacheReset(): void
    {
        $reset = static fn() => MatrixBlockAnchorField::clearStoredAnchors();

        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, $reset);
        Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, $reset);
        Event::on(Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT, $reset);
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
        if (Craft::$app instanceof ConsoleApplication) {
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
     * Lists each entry type, under each Matrix field that offers it, whose field
     * layout includes the anchor field. An entry type shared by several Matrix
     * fields is listed under each of them.
     *
     * @return array<int, array{anchorField: MatrixBlockAnchorField, matrixField: Matrix, entryType: \craft\models\EntryType, matrixFieldUrl: string|null, entryTypeUrl: string|null}> Usage map entries
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _getFieldUsage(): array
    {
        $usage = [];

        foreach (Craft::$app->getFields()->getFieldsByType(Matrix::class) as $matrixField) {
            assert($matrixField instanceof Matrix);

            foreach ($matrixField->getEntryTypes() as $entryType) {
                foreach ($entryType->getFieldLayout()->getCustomFields() as $anchorField) {
                    if (!$anchorField instanceof MatrixBlockAnchorField) {
                        continue;
                    }

                    $usage[] = [
                        'anchorField' => $anchorField,
                        'matrixField' => $matrixField,
                        'entryType' => $entryType,
                        'matrixFieldUrl' => $matrixField->getCpEditUrl(),
                        'entryTypeUrl' => $entryType->getCpEditUrl(),
                    ];
                }
            }
        }

        return $usage;
    }
}
