<?php

namespace johnhenry\matrixblockanchor\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 *
 * @property-read string $anchorPrefix
 */
class MatrixBlockAnchorField extends Field
{
    public const TEMPLATE_PATH = 'matrix-block-anchor/anchor-field/_input';


    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Matrix Block Anchor';
    }

    public function getAnchorPrefix(): string
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->anchorPrefix;
    }


    /**
     * @param  mixed                 $value
     * @param  ElementInterface|null $element
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @return string
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $matrixBlockId = $element?->canonicalId ?? null;

        return Craft::$app->getView()->renderTemplate(
            self::TEMPLATE_PATH,
            [
                'name' => $this->handle,
                'value' => '#'. $this->getAnchorPrefix() .'-' . $matrixBlockId,
            ]
        );
    }
}