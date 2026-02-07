<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\NestedElementInterface;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Matrix Block Anchor Field
 *
 * Provides a custom field type for adding unique anchor IDs to matrix blocks.
 * Validates anchor IDs according to HTML ID rules and ensures uniqueness across blocks.
 *
 * @property-read bool $allowCustomAnchors Whether custom anchors are allowed
 * @property-read array[] $elementValidationRules Validation rules for the field
 * @property-read string $anchorPrefix The prefix to use for anchor IDs
 */
class MatrixBlockAnchorField extends Field
{
    /**
     * Template path for rendering the field input
     */
    public const TEMPLATE_PATH = 'matrix-block-anchor/anchor-field/_input';

    /**
     * Maximum length for anchor IDs
     */
    private const MAX_ANCHOR_LENGTH = 100;

    /**
     * Maximum number of blocks to check for duplicates
     */
    private const MAX_BLOCKS_TO_CHECK = 1000;


    /**
     * Returns the display name of the field type
     *
     * @return string The human-readable field type name
     */
    public static function displayName(): string
    {
        return 'Matrix Block Anchor';
    }

    /**
     * Gets the anchor prefix from plugin settings
     *
     * @return string The configured anchor prefix
     */
    public function getAnchorPrefix(): string
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->anchorPrefix ?? 'blockIdAnchor';
    }

    /**
     * Checks if custom anchors are allowed from plugin settings
     *
     * @return bool True if custom anchors are allowed, false otherwise
     */
    public function getAllowCustomAnchors(): bool
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->allowCustomAnchors ?? false;
    }

    /**
     * Normalizes the value for storage and template output
     *
     * Returns the custom anchor if set, otherwise generates default anchor using prefix + block ID
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The anchor value without hash prefix
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): string
    {
        $allowCustomAnchors = $this->getAllowCustomAnchors();

        // If custom anchors are allowed and a value is set, use it
        if ($allowCustomAnchors && !empty($value)) {
            return $this->removeHashPrefix($value);
        }

        // Otherwise, generate default anchor
        $matrixBlockId = $element?->canonicalId ?? $element?->id ?? '';
        $anchorPrefix = $this->getAnchorPrefix();
        $separator = $this->determineSeparator($anchorPrefix);

        return $anchorPrefix . $separator . $matrixBlockId;
    }

    /**
     * Gets the validation rules for the field
     *
     * @return array<int, array<int, string>> Array of validation rule definitions
     */
    public function getElementValidationRules(): array
    {
        return [
            ['validateAnchorId'],
        ];
    }

    /**
     * Validates the anchor ID according to HTML ID rules
     *
     * Checks that the anchor:
     * - Contains at least one character
     * - Does not start with a number
     * - Does not contain whitespace
     * - Is unique within the entry
     *
     * @param ElementInterface $element The element being validated
     * @return void
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     */
    public function validateAnchorId(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);
        if (empty($value)) {
            return;
        }

        $anchorId = $this->removeHashPrefix($value);

        if (!$this->isValidAnchorFormat($element, $anchorId)) {
            return;
        }

        $this->validateUniqueAnchor($element, $anchorId);
    }

    /**
     * Validates the format of an anchor ID
     *
     * @param ElementInterface $element The element being validated
     * @param string $anchorId The anchor ID to validate
     * @return bool True if the format is valid, false otherwise
     */
    private function isValidAnchorFormat(ElementInterface $element, string $anchorId): bool
    {
        // Check length limit (CWE-400: Resource Consumption)
        if (mb_strlen($anchorId) > self::MAX_ANCHOR_LENGTH) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID must not exceed {max} characters.', ['max' => self::MAX_ANCHOR_LENGTH]));
            return false;
        }

        if ($anchorId === '') {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID must contain at least one character.'));
            return false;
        }

        if (preg_match('/^\d/', $anchorId)) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID cannot start with a number.'));
            return false;
        }

        if (preg_match('/\s/', $anchorId)) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID must not contain whitespaces (spaces, tabs, etc.).'));
            return false;
        }


        // Only allow: a-z, A-Z, 0-9, hyphen, underscore
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $anchorId)) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID can only contain letters, numbers, hyphens, and underscores, and must start with a letter.'));
            return false;
        }

        return true;
    }

    /**
     * Validates that the anchor is unique within the entry
     *
     * Checks all matrix blocks in the owner entry to ensure no duplicate anchor IDs exist.
     *
     * @param ElementInterface $element The element being validated
     * @param string $anchorId The anchor ID to check for uniqueness
     * @return void
     * @throws InvalidConfigException|InvalidFieldException
     */
    private function validateUniqueAnchor(ElementInterface $element, string $anchorId): void
    {
        if (!($element instanceof NestedElementInterface)) {
            return;
        }

        $owner = $element->getOwner();
        if (!$owner) {
            return;
        }

        $fieldLayout = $owner->getFieldLayout();
        if (!$fieldLayout) {
            return;
        }

        if ($this->hasDuplicateAnchorInOwner($owner, $element, $anchorId)) {
            $element->addError(
                $this->handle,
                Craft::t('matrix-block-anchor', 'This anchor ID is already used by another block. Each anchor must be unique.')
            );
        }
    }

    /**
     * Checks if a duplicate anchor exists in the owner's matrix blocks
     *
     * @param ElementInterface $owner The owner element to check
     * @param ElementInterface $currentElement The current element to skip
     * @param string $anchorId The anchor ID to check for duplicates
     * @return bool True if a duplicate is found, false otherwise
     * @throws InvalidFieldException|InvalidConfigException
     */
    private function hasDuplicateAnchorInOwner(ElementInterface $owner, ElementInterface $currentElement, string $anchorId): bool
    {
        $customFields = $owner->getFieldLayout()?->getCustomFields() ?? [];
        $blocksChecked = 0;

        foreach ($customFields as $field) {
            if (!($field instanceof Matrix)) {
                continue;
            }

            $blocks = $owner->getFieldValue($field->handle)->all();

            foreach ($blocks as $block) {
                // Prevent resource exhaustion (CWE-400)
                if (++$blocksChecked > self::MAX_BLOCKS_TO_CHECK) {
                    Craft::warning(
                        "Anchor uniqueness check stopped after {$blocksChecked} blocks for performance",
                        __METHOD__
                    );
                    return false;
                }

                if ($block->id === $currentElement->id) {
                    continue;
                }

                if ($this->blockHasMatchingAnchor($block, $anchorId)) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Checks if a matrix block has a matching anchor ID
     *
     * @param ElementInterface $block The matrix block to check
     * @param string $anchorId The anchor ID to match
     * @return bool True if the block has a matching anchor, false otherwise
     * @throws InvalidFieldException
     */
    private function blockHasMatchingAnchor(ElementInterface $block, string $anchorId): bool
    {
        $blockFields = $block->getFieldLayout()?->getCustomFields() ?? [];

        foreach ($blockFields as $blockField) {
            if ($blockField instanceof self) {
                $blockAnchorValue = $this->removeHashPrefix($block->getFieldValue($blockField->handle) ?? '');
                if ($blockAnchorValue === $anchorId) {
                    return true;
                }
            }
        }

        return false;
    }



    /**
     * Renders the field's input HTML
     *
     * Generates either a custom anchor input or a default anchor display based on plugin settings.
     *
     * @param mixed $value The current field value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The rendered HTML for the field input
     * @throws Exception If there's an error in the rendering process
     * @throws LoaderError If the template cannot be loaded
     * @throws RuntimeError If there's a runtime error in the template
     * @throws SyntaxError If there's a syntax error in the template
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $matrixBlockId = $element?->canonicalId ?? null;
        $anchorPrefix = $this->getAnchorPrefix();
        $allowCustomAnchors = $this->getAllowCustomAnchors();

        $separator = $this->determineSeparator($anchorPrefix);
        $displayValue = $this->generateDisplayValue($value, $anchorPrefix, $separator, $matrixBlockId, $allowCustomAnchors);

        return Craft::$app->getView()->renderTemplate(
            self::TEMPLATE_PATH,
            [
                'name' => $this->handle,
                'value' => $displayValue,
                'allowCustomAnchors' => $allowCustomAnchors,
            ]
        );
    }
    /**
     * Determines the separator to use between prefix and block ID
     *
     * @param string $anchorPrefix The anchor prefix
     * @return string The separator character
     */
    private function determineSeparator(string $anchorPrefix): string
    {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();

        if ($settings->useLegacySeparator ?? false) {
            return '-';
        }

        return '';
    }

    /**
     * Generates the display value for the anchor field
     *
     * @param mixed $value The stored field value
     * @param string $anchorPrefix The anchor prefix
     * @param string $separator The separator character
     * @param mixed $matrixBlockId The matrix block ID
     * @param bool $allowCustomAnchors Whether custom anchors are allowed
     * @return string The formatted display value with hash prefix
     */
    private function generateDisplayValue(
        mixed $value,
        string $anchorPrefix,
        string $separator,
        mixed $matrixBlockId,
        bool $allowCustomAnchors,
    ): string {
        if ($allowCustomAnchors && !empty($value)) {
            return '#' . $this->removeHashPrefix($value);
        }

        return '#' . $anchorPrefix . $separator . $matrixBlockId;
    }

    /**
     * Removes the hash prefix from a value
     *
     * @param string $value The value to process
     * @return string The value without the hash prefix
     */
    private function removeHashPrefix(string $value): string
    {
        return ltrim($value, '#');
    }

}
