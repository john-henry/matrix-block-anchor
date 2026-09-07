<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\NestedElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
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
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class MatrixBlockAnchorField extends Field implements PreviewableFieldInterface
{
    // =========================================================================
    // Constants
    // =========================================================================

    /**
     * Template path for rendering the field input
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public const TEMPLATE_PATH = 'matrix-block-anchor/anchor-field/_input';

    /**
     * Maximum length for anchor IDs
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private const MAX_ANCHOR_LENGTH = 100;

    /**
     * Maximum number of blocks to check for duplicates
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private const MAX_BLOCKS_TO_CHECK = 1000;

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of the field type
     *
     * @return string The human-readable field type name
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function displayName(): string
    {
        return 'Matrix Block Anchor';
    }

    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Gets the anchor prefix from plugin settings
     *
     * @return string The configured anchor prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getAnchorPrefix(): string
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->anchorPrefix ?? 'blockIdAnchor';
    }

    /**
     * Checks if custom anchors are allowed from plugin settings
     *
     * @return bool True if custom anchors are allowed, false otherwise
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getAllowCustomAnchors(): bool
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->getAllowCustomAnchors();
    }

    /**
     * Normalizes the value for storage and template output
     *
     * Returns the custom anchor when one is set, otherwise builds the default anchor from the
     * prefix and block ID. The custom anchor is always sanitized here, so values coming in from
     * outside the CP (Feed Me imports, console resaves, programmatic saves) stay within the safe
     * character set even though they never run through validateAnchorId().
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The anchor value without hash prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): string
    {
        $allowCustomAnchors = $this->getAllowCustomAnchors();

        // If custom anchors are allowed and a string value is set, use it (sanitized).
        // Craft passes raw request input through here, so a non-string (e.g. an array from
        // a crafted `fields[handle][]=x` body param) must fall through to auto-generation
        // rather than reach the string-typed helpers below and throw.
        if ($allowCustomAnchors && is_string($value) && $value !== '') {
            $sanitized = $this->sanitizeAnchorId($this->removeHashPrefix($value));
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        // Otherwise, generate default anchor
        $matrixBlockId = $element?->canonicalId ?? $element?->id ?? '';
        $anchorPrefix = $this->getAnchorPrefix();
        $separator = $this->determineSeparator();

        return $anchorPrefix . $separator . $matrixBlockId;
    }

    /**
     * Gets the validation rules for the field
     *
     * @return array<int, array<int, string>> Array of validation rule definitions
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
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
     * - Is unique within the owner, per site
     *
     * @param ElementInterface $element The element being validated
     * @return void
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     * @throws \yii\db\Exception If the duplicate-anchor lookup query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function isValidAnchorFormat(ElementInterface $element, string $anchorId): bool
    {
        // Cap the length before running any other checks.
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
     * Validates that the anchor is unique within the owner, per site
     *
     * Checks all matrix blocks belonging to the owner element (scoped to the owner's site
     * via {@see NestedElementInterface::primaryOwner()}) to ensure no duplicate anchor IDs exist.
     *
     * @param ElementInterface $element The element being validated
     * @param string $anchorId The anchor ID to check for uniqueness
     * @return void
     * @throws InvalidConfigException|InvalidFieldException
     * @throws \yii\db\Exception If the duplicate-anchor lookup query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function validateUniqueAnchor(ElementInterface $element, string $anchorId): void
    {
        if ($element->getIsRevision()) {
            return;
        }

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

        // On a draft save, if the anchor still matches the canonical value it hasn't changed,
        // so skip the uniqueness check to avoid a false duplicate error on resave. Canonical
        // elements return themselves from getCanonical(), so this only applies to drafts.
        if (!$element->getIsCanonical()) {
            $canonical = $element->getCanonical();
            if ($canonical) { // @phpstan-ignore-line
                $stored = $this->readAnchorValue($canonical);
                if ($stored !== null && $stored === $anchorId) {
                    return;
                }
            }
        }

        if ($this->hasDuplicateAnchorInOwner($owner, $element, $anchorId)) {
            $element->addError(
                $this->handle,
                Craft::t('matrix-block-anchor', 'This anchor ID is already used by another block. Each anchor must be unique.')
            );
        }
    }

    /**
     * Reads this field's anchor value off an element, or null when the element can't hold one.
     *
     * The element's own field layout is checked first: a block whose entry type doesn't include
     * this field would make getFieldValue() throw. That happens on a draft save after a block's
     * entry type has been switched from a type without the anchor field to one with it, because
     * the canonical block still has the old type.
     *
     * @param ElementInterface $element The element to read the anchor from
     * @return string|null The anchor without its hash prefix, or null when the element's layout
     *                     doesn't include this field or the stored value isn't a string
     * @throws InvalidFieldException
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.3.1
     */
    private function readAnchorValue(ElementInterface $element): ?string
    {
        if (!$element->getFieldLayout()?->getFieldByHandle($this->handle) instanceof self) {
            return null;
        }

        $value = $element->getFieldValue($this->handle);

        return is_string($value) ? $this->removeHashPrefix($value) : null;
    }

    /**
     * Checks if a duplicate anchor exists in the owner's matrix blocks
     *
     * @param ElementInterface $owner The owner element to check
     * @param ElementInterface $currentElement The current element to skip
     * @param string $anchorId The anchor ID to check for duplicates
     * @return bool True if a duplicate is found, false otherwise
     * @throws InvalidFieldException|InvalidConfigException
     * @throws \yii\db\Exception If the underlying `Entry::find()->all()` query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function hasDuplicateAnchorInOwner(ElementInterface $owner, ElementInterface $currentElement, string $anchorId): bool
    {
        /** @var ElementInterface|null $canonicalOwner */
        $canonicalOwner = $owner->getIsCanonical() ? $owner : $owner->getCanonical();
        if (!$canonicalOwner) {
            return false;
        }

        $currentCanonicalId = $currentElement->getIsCanonical()
            ? $currentElement->id
            : ($currentElement->getCanonical()?->id ?? $currentElement->id);

        // Query the blocks straight off primaryOwner rather than the owner's field-layout
        // cache, which is often stale mid-validation.
        $blocks = Entry::find()
            ->primaryOwner($canonicalOwner)
            ->status(null)
            ->limit(self::MAX_BLOCKS_TO_CHECK)
            ->all();

        // The query is capped at MAX_BLOCKS_TO_CHECK. If we hit that cap there may be more
        // blocks we never looked at, so the result is best-effort rather than a guarantee.
        // Log it so a missed duplicate on a very large owner can be traced in web.log.
        if (count($blocks) >= self::MAX_BLOCKS_TO_CHECK) {
            Craft::warning(
                'Anchor uniqueness check truncated at ' . self::MAX_BLOCKS_TO_CHECK
                . ' blocks for owner element #' . $canonicalOwner->id . ' (' . $canonicalOwner::class . '); '
                . 'duplicates beyond this cap will not be detected.',
                'matrix-block-anchor'
            );
        }

        foreach ($blocks as $block) {
            if ($block->id === $currentCanonicalId) {
                continue;
            }

            if ($this->blockHasMatchingAnchor($block, $anchorId)) {
                return true;
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function blockHasMatchingAnchor(ElementInterface $block, string $anchorId): bool
    {
        $blockFields = $block->getFieldLayout()?->getCustomFields() ?? [];

        foreach ($blockFields as $blockField) {
            if ($blockField instanceof self) {
                // getFieldValue() already returns the normalized/sanitized value, so this
                // compares like-for-like against $anchorId. Don't swap this for a raw column read.
                $blockAnchorValue = $block->getFieldValue($blockField->handle);
                if (is_string($blockAnchorValue) && $this->removeHashPrefix($blockAnchorValue) === $anchorId) {
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $matrixBlockId = $element?->canonicalId ?? null;
        $anchorPrefix = $this->getAnchorPrefix();
        $allowCustomAnchors = $this->getAllowCustomAnchors();

        $separator = $this->determineSeparator();
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
     * Returns the HTML displayed in the element index preview column.
     *
     * @param mixed $value The normalised field value
     * @param ElementInterface $element The element the field belongs to
     * @return string The preview HTML, or an empty string when no value is set
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        return '<code>' . htmlspecialchars('#' . ltrim($value, '#')) . '</code>';
    }

    /**
     * Returns placeholder HTML shown in the preview column when no value is stored.
     *
     * @param mixed $value The normalised field value
     * @param ElementInterface|null $element The element the field belongs to
     * @return string The placeholder HTML
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $prefix = $this->getAnchorPrefix();
        return '<code>#' . htmlspecialchars($prefix) . '…</code>';
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Determines the separator to use between prefix and block ID
     *
     * Controlled solely by the "Use Legacy Separator" setting: a hyphen when it's on, nothing
     * when it's off. The prefix has no bearing on whether a separator is emitted.
     *
     * @return string The separator character
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 2.0.0
     */
    private function determineSeparator(): string
    {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();

        if ($settings->getUseLegacySeparator()) {
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function generateDisplayValue(
        mixed $value,
        string $anchorPrefix,
        string $separator,
        mixed $matrixBlockId,
        bool $allowCustomAnchors,
    ): string {
        if ($allowCustomAnchors && is_string($value) && $value !== '') {
            return '#' . $this->removeHashPrefix($value);
        }

        return '#' . $anchorPrefix . $separator . $matrixBlockId;
    }

    /**
     * Removes the hash prefix from a value
     *
     * @param string $value The value to process
     * @return string The value without the hash prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function removeHashPrefix(string $value): string
    {
        return ltrim($value, '#');
    }

    /**
     * Sanitizes a candidate anchor ID down to the safe character set.
     *
     * Strips anything outside `[a-zA-Z0-9_-]`, then strips any leading characters that aren't
     * letters so the result always starts with a letter. This runs on every write path,
     * including the ones that skip {@see validateAnchorId()} (imports, console resaves,
     * programmatic saves), so the safe-character rule holds no matter how the value arrived.
     *
     * @param string $value The candidate anchor ID, already stripped of any hash prefix
     * @return string The sanitized anchor ID, or an empty string if nothing safe remains
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.3.0
     */
    private function sanitizeAnchorId(string $value): string
    {
        $stripped = preg_replace('/[^a-zA-Z0-9_-]/', '', $value) ?? '';
        $withoutLeadingNonLetters = preg_replace('/^[^a-zA-Z]+/', '', $stripped) ?? '';

        return $withoutLeadingNonLetters;
    }
}
