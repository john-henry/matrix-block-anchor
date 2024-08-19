<?php

namespace johnhenry\matrixblockanchor;

use Craft;
use craft\base\Plugin as BasePlugin;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\View;

use johnhenry\matrixblockanchor\assetbundles\MatrixBlockAnchorAssets;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\models\Settings;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;


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
                'settings' => $this->getSettings()
            ]
        );
    }
}
