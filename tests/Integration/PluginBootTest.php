<?php

/**
 * The plugin's wiring lives in a trait; this is the check that it is all still
 * attached to the class that uses it.
 */

use johnhenry\matrixblockanchor\assetbundles\MatrixBlockAnchorAssets;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use johnhenry\matrixblockanchor\models\Settings;

describe('Plugin wiring', function() {
    it('builds its settings model', function() {
        expect(MatrixBlockAnchor::getInstance()->getSettings())->toBeInstanceOf(Settings::class);
    });

    it('registers the anchor field type', function() {
        expect(Craft::$app->getFields()->getAllFieldTypes())->toContain(MatrixBlockAnchorField::class);
    });

    it('renders the settings screen, usage map and all', function() {
        Craft::$app->getView()->setTemplateMode(craft\web\View::TEMPLATE_MODE_CP);

        // settingsHtml() is protected: Craft reaches it through the settings
        // response, and the trait is what has to still provide it.
        $method = new ReflectionMethod(MatrixBlockAnchor::class, 'settingsHtml');
        $html = $method->invoke(MatrixBlockAnchor::getInstance());

        expect($html)->toBeString()
            ->and(strlen($html))->toBeGreaterThan(200);
    });

    it('registers the control panel asset bundle on a CP render', function() {
        $request = Craft::$app->getRequest();
        $wasCp = $request->getIsCpRequest();
        $request->setIsCpRequest(true);

        try {
            $view = Craft::$app->getView();
            $view->setTemplateMode(craft\web\View::TEMPLATE_MODE_CP);
            $view->renderTemplate('matrix-block-anchor/_settings', [
                'settings' => MatrixBlockAnchor::getInstance()->getSettings(),
                'config' => [],
                'fieldUsage' => [],
            ]);
        } finally {
            $request->setIsCpRequest($wasCp);
        }

        expect(array_keys($view->assetBundles))->toContain(MatrixBlockAnchorAssets::class);
    });
});
