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
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;

/**
 * Matrix Block Anchor Field
 *
 * Gives each Matrix block an anchor ID: built from the prefix and the block ID,
 * or typed by an editor when custom anchors are allowed. Only typed anchors are
 * stored; auto anchors are rebuilt from the block's own ID on every load, so a
 * duplicated block never inherits another block's anchor.
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
    // Const Properties
    // =========================================================================

    /**
     * @var string Template path for rendering the field input
     */
    public const TEMPLATE_PATH = 'matrix-block-anchor/anchor-field/_input';

    /**
     * @var int Maximum length for anchor IDs
     */
    private const MAX_ANCHOR_LENGTH = 100;

    /**
     * @var string Prefix used when the configured one resolves to nothing usable
     */
    private const DEFAULT_PREFIX = 'blockIdAnchor';

    // =========================================================================
    // Static Properties
    // =========================================================================

    /**
     * @var array<string, array<int, array{anchor: string, fieldId: int|null}>> Typed anchors stored on each
     * owner's blocks, keyed `ownerId:siteId` and then by block ID. Built once per owner per request, and
     * cleared whenever an element is saved, deleted or restored.
     */
    private static array $_storedAnchors = [];

    // =========================================================================
    // Public Methods
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

    /**
     * Clears the stored anchors built for uniqueness checks, so a later check
     * in the same request sees the latest saved values.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    public static function clearStoredAnchors(): void
    {
        self::$_storedAnchors = [];
    }

    /**
     * Returns the resolved anchor prefix, reduced to the characters an anchor
     * may contain, or `blockIdAnchor` when nothing usable is left.
     *
     * @return string The anchor prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getAnchorPrefix(): string
    {
        $prefix = $this->_sanitizeAnchorId(MatrixBlockAnchor::getInstance()->getSettings()->getAnchorPrefix());

        return $prefix !== '' ? $prefix : self::DEFAULT_PREFIX;
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
        return (bool)MatrixBlockAnchor::getInstance()->getSettings()->getAllowCustomAnchors();
    }

    /**
     * Normalizes the value for template output
     *
     * Returns the custom anchor when custom anchors are allowed and one is set, otherwise the
     * block's auto anchor. A custom anchor is cleaned down to the safe character set here, so
     * values from imports, console resaves and programmatic saves are safe to output even though
     * they never run through validation.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The anchor value without hash prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): string
    {
        // A non-string (e.g. an array from a crafted `fields[handle][]=x` body param) falls through
        // to the auto anchor rather than reaching the string helpers.
        if ($this->getAllowCustomAnchors() && is_string($value)) {
            $sanitized = $this->_sanitizeAnchorId($this->_removeHashPrefix($value));
            if ($sanitized !== '' && !$this->_isAutoAnchor($sanitized)) {
                return $sanitized;
            }
        }

        return $this->_autoAnchor($element);
    }

    /**
     * Normalizes a posted value, keeping what the editor typed so validation
     * can report a problem instead of silently rewriting it.
     *
     * @param mixed $value The posted value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The typed anchor without hash prefix, or the normalized value
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): string
    {
        if ($this->getAllowCustomAnchors() && is_string($value)) {
            $typed = $this->_removeHashPrefix(trim($value));
            if ($typed !== '' && !$this->_isAutoAnchor($typed)) {
                return $typed;
            }
        }

        return $this->normalizeValue($value, $element);
    }

    /**
     * Stores only anchors an editor typed. Auto anchors are stored as null and
     * rebuilt from the block ID on load.
     *
     * @param mixed $value The normalized value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string|null The cleaned custom anchor, or null
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $sanitized = $this->_sanitizeAnchorId($this->_removeHashPrefix($value));

        return $sanitized !== '' && !$this->_isAutoAnchor($sanitized) ? $sanitized : null;
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
     * Validates a typed anchor: its format, and that no other block on the owner
     * uses it. Auto anchors are always valid and unique, so they're skipped.
     *
     * @param ElementInterface $element The element being validated
     * @return void
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     * @throws DbException If the stored anchor lookup query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function validateAnchorId(ElementInterface $element): void
    {
        if (!$this->getAllowCustomAnchors()) {
            return;
        }

        $value = $element->getFieldValue($this->handle);
        if (!is_string($value) || $value === '') {
            return;
        }

        $anchorId = $this->_removeHashPrefix($value);

        if ($this->_isAutoAnchor($anchorId) || !$this->_isValidAnchorFormat($element, $anchorId)) {
            return;
        }

        $this->_validateUniqueAnchor($element, $anchorId);
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
        if (!is_string($value) || $value === '') {
            return '';
        }

        return '<code>' . htmlspecialchars('#' . $this->_removeHashPrefix($value)) . '</code>';
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
        return '<code>#' . htmlspecialchars($this->getAnchorPrefix()) . '…</code>';
    }

    /**
     * Renders the field read-only, with the copy button still working, for
     * revisions and users who can only view the entry.
     *
     * @param mixed $value The normalized field value
     * @param ElementInterface $element The element the field belongs to
     * @return string The rendered HTML
     * @throws Exception|LoaderError|RuntimeError|SyntaxError If the template can't be rendered
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_renderInput($value, $element, true);
    }

    // =========================================================================
    // Protected Methods
    // =========================================================================

    /**
     * Renders the field input: editable when custom anchors are allowed,
     * otherwise read-only so the anchor can still be focused and copied.
     *
     * @param mixed $value The normalized value, or the raw posted value after a validation error
     * @param ElementInterface|null $element The element the field is associated with
     * @param bool $inline Whether this is for an inline edit form
     * @return string The rendered HTML
     * @throws Exception|LoaderError|RuntimeError|SyntaxError If the template can't be rendered
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_renderInput($value, $element, false);
    }

    // =========================================================================
    // Private Methods
    // =========================================================================

    /**
     * Renders the input template.
     *
     * @param mixed $value The field value
     * @param ElementInterface|null $element The element the field is associated with
     * @param bool $static Whether to render read-only with no posted input
     * @return string The rendered HTML
     * @throws Exception|LoaderError|RuntimeError|SyntaxError If the template can't be rendered
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _renderInput(mixed $value, ?ElementInterface $element, bool $static): string
    {
        $allowCustomAnchors = $this->getAllowCustomAnchors();
        $anchor = $allowCustomAnchors && is_string($value) && $this->_removeHashPrefix($value) !== ''
            ? $this->_removeHashPrefix($value)
            : $this->_autoAnchor($element);

        return Craft::$app->getView()->renderTemplate(self::TEMPLATE_PATH, [
            'field' => $this,
            'name' => $static ? null : $this->handle,
            'value' => '#' . $anchor,
            'readonly' => $static || !$allowCustomAnchors,
        ]);
    }

    /**
     * Builds the auto anchor for an element from the prefix, the separator and
     * the block's canonical ID.
     *
     * @param ElementInterface|null $element The element
     * @return string The auto anchor
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _autoAnchor(?ElementInterface $element): string
    {
        return $this->getAnchorPrefix() . $this->_determineSeparator() . ($element?->getCanonicalId() ?? '');
    }

    /**
     * Returns whether an anchor has the shape of an auto anchor: the prefix and
     * separator followed only by digits (or nothing, for a block not yet saved).
     *
     * @param string $anchorId The anchor without hash prefix
     * @return bool Whether it's an auto anchor
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _isAutoAnchor(string $anchorId): bool
    {
        $pattern = '/^' . preg_quote($this->getAnchorPrefix() . $this->_determineSeparator(), '/') . '\d*$/';

        return preg_match($pattern, $anchorId) === 1;
    }

    /**
     * Validates the format of a typed anchor, adding an error when it fails.
     *
     * @param ElementInterface $element The element being validated
     * @param string $anchorId The anchor ID to validate
     * @return bool True if the format is valid, false otherwise
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _isValidAnchorFormat(ElementInterface $element, string $anchorId): bool
    {
        if (mb_strlen($anchorId) > self::MAX_ANCHOR_LENGTH) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID must not exceed {max} characters.', ['max' => self::MAX_ANCHOR_LENGTH]));
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

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $anchorId)) {
            $element->addError($this->handle, Craft::t('matrix-block-anchor', 'Anchor ID can only contain letters, numbers, hyphens, and underscores, and must start with a letter.'));
            return false;
        }

        return true;
    }

    /**
     * Validates that no other block on the owner uses the anchor, per site.
     *
     * @param ElementInterface $element The element being validated
     * @param string $anchorId The anchor ID to check for uniqueness
     * @return void
     * @throws InvalidConfigException|InvalidFieldException
     * @throws DbException If the stored anchor lookup query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _validateUniqueAnchor(ElementInterface $element, string $anchorId): void
    {
        if ($element->getIsRevision() || !$element instanceof NestedElementInterface) {
            return;
        }

        $owner = $element->getOwner();
        if ($owner === null || $owner->getFieldLayout() === null) {
            return;
        }

        // A draft whose anchor matches its canonical block's hasn't changed it, so it can't have
        // introduced a clash; skipping keeps an existing duplicate from blocking unrelated edits.
        if (!$element->getIsCanonical() && $this->_readAnchorValue($element->getCanonical()) === $anchorId) {
            return;
        }

        // Likewise for a saved block whose anchor is unchanged: the error belongs on the block
        // that just took the anchor, not the one that already had it.
        $currentId = $element->getCanonicalId();
        if ($currentId !== null && ($this->_storedAnchorsFor($owner)[$currentId]['anchor'] ?? null) === $anchorId) {
            return;
        }

        if ($this->_hasDuplicateAnchorInOwner($owner, $element, $anchorId)) {
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
     * this field would make getFieldValue() throw. That's the case for a draft block whose entry
     * type was switched to one with the anchor field, because its canonical block still has the
     * old type.
     *
     * @param ElementInterface $element The element to read the anchor from
     * @return string|null The anchor without its hash prefix, or null when the element's layout
     *                     doesn't include this field or the stored value isn't a string
     * @throws InvalidFieldException
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.3.1
     */
    private function _readAnchorValue(ElementInterface $element): ?string
    {
        if (!$element->getFieldLayout()?->getFieldByHandle($this->handle) instanceof self) {
            return null;
        }

        $value = $element->getFieldValue($this->handle);

        return is_string($value) ? $this->_removeHashPrefix($value) : null;
    }

    /**
     * Checks if another block on the owner already has the anchor
     *
     * Blocks being saved alongside this one are compared by their unsaved values, taken from the
     * owner's Matrix field, so two blocks given the same new anchor in one save are caught and two
     * blocks can swap anchors. Everything else is compared by its stored value.
     *
     * @param ElementInterface $owner The owner element
     * @param NestedElementInterface&ElementInterface $currentElement The block being validated
     * @param string $anchorId The anchor ID to check for duplicates
     * @return bool True if a duplicate is found, false otherwise
     * @throws InvalidFieldException|InvalidConfigException
     * @throws DbException If the stored anchor lookup query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _hasDuplicateAnchorInOwner(ElementInterface $owner, NestedElementInterface $currentElement, string $anchorId): bool
    {
        $currentId = $currentElement->getCanonicalId();
        $siblings = $this->_unsavedSiblings($owner, $currentElement);

        foreach ($siblings as $sibling) {
            if ($sibling === $currentElement || ($currentId !== null && $sibling->getCanonicalId() === $currentId)) {
                continue;
            }

            if ($this->_readAnchorValue($sibling) === $anchorId) {
                return true;
            }
        }

        // With the Matrix field's blocks in memory, its stored rows are out of date: a block
        // removed in this save, or one whose anchor is changing, would otherwise still count.
        $skipFieldId = $siblings !== [] ? $currentElement->getField()?->id : null;

        foreach ($this->_storedAnchorsFor($owner) as $blockId => $stored) {
            if ($blockId === $currentId || ($skipFieldId !== null && $stored['fieldId'] === $skipFieldId)) {
                continue;
            }

            if ($stored['anchor'] === $anchorId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the blocks the owner holds in memory for this block's Matrix field.
     *
     * When Craft validates an owner's Matrix field, it validates each block with the owner set, and
     * the owner's field value still holds the blocks being saved. A block saved on its own (from a
     * card or slideout) has no such set, so this returns nothing.
     *
     * @param ElementInterface $owner The owner element
     * @param NestedElementInterface $block The block being validated
     * @return ElementInterface[] The in-memory blocks, or an empty array
     * @throws InvalidConfigException
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _unsavedSiblings(ElementInterface $owner, NestedElementInterface $block): array
    {
        $matrixField = $block->getField();
        if ($matrixField === null || $owner->getFieldLayout()?->getFieldByHandle($matrixField->handle) === null) {
            return [];
        }

        $value = $owner->getFieldValue($matrixField->handle);

        return $value instanceof EntryQuery ? ($value->getCachedResult() ?? []) : [];
    }

    /**
     * Returns the typed anchors stored on the owner's blocks, keyed by block ID.
     *
     * Reads the canonical owner's blocks in the owner's site. Built once per owner per request,
     * because a save validates every block and reloading them all for each one is quadratic.
     *
     * @param ElementInterface $owner The owner element
     * @return array<int, array{anchor: string, fieldId: int|null}> Anchors keyed by block ID
     * @throws InvalidFieldException
     * @throws DbException If the query fails
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    private function _storedAnchorsFor(ElementInterface $owner): array
    {
        $canonicalOwner = $owner->getCanonical();
        $key = $canonicalOwner->id . ':' . $canonicalOwner->siteId;

        if (isset(self::$_storedAnchors[$key])) {
            return self::$_storedAnchors[$key];
        }

        $anchors = [];
        $blocks = Entry::find()
            ->primaryOwner($canonicalOwner)
            ->status(null)
            ->all();

        foreach ($blocks as $block) {
            foreach ($block->getFieldLayout()?->getCustomFields() ?? [] as $blockField) {
                if (!$blockField instanceof self) {
                    continue;
                }

                $anchor = $block->getFieldValue($blockField->handle);
                if (is_string($anchor) && !$this->_isAutoAnchor($anchor)) {
                    $anchors[$block->id] = ['anchor' => $anchor, 'fieldId' => $block->fieldId];
                }
            }
        }

        return self::$_storedAnchors[$key] = $anchors;
    }

    /**
     * Determines the separator to use between prefix and block ID
     *
     * Controlled solely by the "Use Legacy Separator" setting: a hyphen when it's on, nothing
     * when it's off.
     *
     * @return string The separator character
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 2.0.0
     */
    private function _determineSeparator(): string
    {
        return MatrixBlockAnchor::getInstance()->getSettings()->getUseLegacySeparator() ? '-' : '';
    }

    /**
     * Removes the hash prefix from a value
     *
     * @param string $value The value to process
     * @return string The value without the hash prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _removeHashPrefix(string $value): string
    {
        return ltrim($value, '#');
    }

    /**
     * Cleans a candidate anchor ID down to the safe character set.
     *
     * Strips anything outside `[a-zA-Z0-9_-]`, then any leading characters that aren't letters so
     * the result starts with a letter, and caps the length. Custom anchors and the prefix both go
     * through here, so every anchor is safe to output however it was saved.
     *
     * @param string $value The candidate anchor ID, already stripped of any hash prefix
     * @return string The cleaned anchor ID, or an empty string if nothing safe remains
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.3.0
     */
    private function _sanitizeAnchorId(string $value): string
    {
        $stripped = preg_replace('/[^a-zA-Z0-9_-]/', '', $value) ?? '';
        $startsWithLetter = preg_replace('/^[^a-zA-Z]+/', '', $stripped) ?? '';

        return substr($startsWithLetter, 0, self::MAX_ANCHOR_LENGTH);
    }
}
