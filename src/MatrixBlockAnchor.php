<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor;

use Craft;
use craft\base\Plugin as BasePlugin;

use craft\events\RegisterComponentTypesEvent;
use craft\fields\Matrix;
use craft\models\EntryType;
use craft\services\Fields;
use craft\web\View;

use johnhenry\matrixblockanchor\assetbundles\MatrixBlockAnchorAssets;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\models\Settings;

use yii\base\Event;

/**
 * Matrix Block Anchor plugin
 *
 * @method static MatrixBlockAnchor getInstance()
 * @author John Henry Donovan <info@johnhenry.ie>
 * @copyright John Henry Donovan
 * @license https://craftcms.github.io/license/ Craft License
 */
class MatrixBlockAnchor extends BasePlugin
{
    public bool $hasCpSection = false;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.0.0';


    public function init(): void
    {
        parent::init();

        $this->_registerField();
        $this->_registerAssets();
    }


    private function _registerField(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = MatrixBlockAnchorField::class;
            });
    }

    private function _registerAssets(): void
    {
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

    protected function createSettingsModel(): models\Settings
    {
        return new Settings();
    }


    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'matrix-block-anchor/settings',
            [
                'settings' => $this->getSettings(),
                'fieldUsage' => $this->_getFieldUsage(),
            ]
        );
    }

    private function _getFieldUsage(): array
    {
        $usage = [];
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        // Find all MatrixBlockAnchorField instances
        $anchorFields = $fieldsService->getFieldsByType(MatrixBlockAnchorField::class);

        foreach ($anchorFields as $anchorField) {
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
