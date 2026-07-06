<?php

use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;

// ---------------------------------------------------------------------------
// Static metadata
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField metadata', function() {
    it('returns the correct display name', function() {
        expect(MatrixBlockAnchorField::displayName())->toBe('Matrix Block Anchor');
    });

    it('returns a validateAnchorId validation rule', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $rules = $field->getElementValidationRules();

        expect($rules)->toBeArray()->not->toBeEmpty()
            ->and($rules[0])->toContain('validateAnchorId');
    });

    it('implements PreviewableFieldInterface', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field)->toBeInstanceOf(PreviewableFieldInterface::class);
    });
});

// ---------------------------------------------------------------------------
// getPreviewHtml
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::getPreviewHtml()', function() {
    it('returns empty string for an empty value', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = Closure::bind(
            fn() => $this->createMock(\craft\base\Element::class),
            $this,
            \PHPUnit\Framework\TestCase::class
        )();

        expect($field->getPreviewHtml('', $element))->toBe('');
    });

    it('wraps the anchor in a code tag with a hash prefix', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = Closure::bind(
            fn() => $this->createMock(\craft\base\Element::class),
            $this,
            \PHPUnit\Framework\TestCase::class
        )();

        expect($field->getPreviewHtml('my-anchor', $element))->toBe('<code>#my-anchor</code>');
    });

    it('strips a leading hash before wrapping', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = Closure::bind(
            fn() => $this->createMock(\craft\base\Element::class),
            $this,
            \PHPUnit\Framework\TestCase::class
        )();

        expect($field->getPreviewHtml('#my-anchor', $element))->toBe('<code>#my-anchor</code>');
    });

    it('escapes HTML in the value so the preview can\'t inject markup', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = Closure::bind(
            fn() => $this->createMock(\craft\base\Element::class),
            $this,
            \PHPUnit\Framework\TestCase::class
        )();

        expect($field->getPreviewHtml('<b>x</b>', $element))->toBe('<code>#&lt;b&gt;x&lt;/b&gt;</code>');
    });
});

// ---------------------------------------------------------------------------
// previewPlaceholderHtml
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::previewPlaceholderHtml()', function() {
    it('shows the prefix with a trailing ellipsis', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = MatrixBlockAnchor::getInstance()->getSettings()->anchorPrefix;

        expect($field->previewPlaceholderHtml('', null))->toBe('<code>#' . $prefix . '…</code>');
    });
});

// ---------------------------------------------------------------------------
// normalizeValue — auto-generated anchors (custom anchors disabled)
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::normalizeValue() auto-generation', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = false;
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    it('returns the prefix alone when element is null', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        expect($field->normalizeValue(null, null))->toBe($prefix);
    });

    it('ignores a stored value when custom anchors are disabled', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        expect($field->normalizeValue('my-custom-anchor', null))->toBe($prefix);
    });
});

// ---------------------------------------------------------------------------
// normalizeValue — legacy separator
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::normalizeValue() legacy separator', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->origUseLegacySeparator = $this->settings->useLegacySeparator;
        $this->settings->allowCustomAnchors = false;
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
        $this->settings->useLegacySeparator = $this->origUseLegacySeparator;
    });

    it('appends a hyphen between the prefix and block ID when enabled', function() {
        $this->settings->useLegacySeparator = true;
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        expect($field->normalizeValue(null, null))->toBe($prefix . '-');
    });

    it('omits the separator when disabled', function() {
        $this->settings->useLegacySeparator = false;
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        expect($field->normalizeValue(null, null))->toBe($prefix);
    });
});

// ---------------------------------------------------------------------------
// normalizeValue — custom anchors enabled
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::normalizeValue() custom anchors', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = true;
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    it('returns the stored value when custom anchors are enabled', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field->normalizeValue('my-anchor', null))->toBe('my-anchor');
    });

    it('strips a leading hash from the stored value', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field->normalizeValue('#my-anchor', null))->toBe('my-anchor');
    });

    it('falls back to auto-generation when stored value is empty', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        $result = $field->normalizeValue('', null);

        expect(str_starts_with($result, $prefix))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// normalizeValue — sanitization on write paths that bypass validateAnchorId()
//
// validateAnchorId() only runs via Element::afterValidate() for the editable
// CP save scenario. Feed Me imports, console resaves, and any programmatic
// setFieldValue() + saveElement() call can bypass it entirely, so
// normalizeValue() must sanitize unconditionally to hold the safe-character
// invariant regardless of write path.
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::normalizeValue() unconditional sanitization', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = true;
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    it('strips disallowed characters from an unsanitized value', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field->normalizeValue('my anchor!<script>', null))->toBe('myanchorscript');
    });

    it('strips leading non-letter characters so the result still starts with a letter', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field->normalizeValue('123-invalid', null))->toBe('invalid');
    });

    it('falls back to auto-generation when sanitization leaves nothing safe', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        $result = $field->normalizeValue('123 456 !!!', null);

        expect(str_starts_with($result, $prefix))->toBeTrue();
    });

    it('leaves an already-safe value untouched', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);

        expect($field->normalizeValue('Section_1-intro', null))->toBe('Section_1-intro');
    });

    it('falls back to auto-generation on non-string input instead of crashing', function() {
        // Craft hands raw request input to normalizeValue(), so a crafted array body param
        // (fields[handle][]=x) must not reach the string-typed helpers and throw a TypeError.
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $prefix = $this->settings->anchorPrefix;

        expect($field->normalizeValue(['x'], null))->toBe($prefix)
            ->and($field->normalizeValue(['foo' => 'bar'], null))->toBe($prefix);
    });
});

// ---------------------------------------------------------------------------
// validateAnchorId — format rules
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField::validateAnchorId() format validation', function() {
    beforeEach(function() {
        // Closure::bind gives the factory access to TestCase::createMock() (protected).
        // We mock craft\base\Element (not ElementInterface) because addError() is
        // inherited from yii\base\Model and is not declared on the interface — PHPUnit
        // can only configure methods that exist on the type being mocked.
        // craft\base\Element does not implement NestedElementInterface, so
        // validateUniqueAnchor() returns early and only format rules run.
        $this->mockElement = Closure::bind(
            function(string $value, array &$errors): ElementInterface {
                $element = $this->createMock(\craft\base\Element::class);

                $element->method('getFieldValue')
                    ->willReturn($value);

                $element->method('addError')
                    ->willReturnCallback(static function(string $attr, string $msg) use (&$errors): void {
                        $errors[$attr][] = $msg;
                    });

                return $element;
            },
            $this,
            \PHPUnit\Framework\TestCase::class
        );
    });

    it('adds no error for an empty value (skips validation)', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toBeEmpty();
    });

    it('adds no error for a valid anchor', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('valid-anchor', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toBeEmpty();
    });

    it('adds no error for an anchor with letters, digits, hyphens, and underscores', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('Section_1-intro', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toBeEmpty();
    });

    it('adds no error when value has a leading hash (stripped before validation)', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('#valid-anchor', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toBeEmpty();
    });

    it('adds an error when the anchor starts with a digit', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('1invalid', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('adds an error when the anchor contains a space', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('has space', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('adds an error when the anchor contains a tab', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)("has\ttab", $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('adds an error when the anchor contains a special character', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('invalid!char', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('adds an error when the anchor contains a dot', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $element = ($this->mockElement)('invalid.dot', $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('adds an error when the anchor exceeds the 100-character limit', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $longAnchor = 'a' . str_repeat('b', 100); // 101 chars
        $element = ($this->mockElement)($longAnchor, $errors);

        $field->validateAnchorId($element);

        expect($errors)->toHaveKey('anchor');
    });

    it('accepts an anchor of exactly 100 characters', function() {
        $errors = [];
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $maxAnchor = 'a' . str_repeat('b', 99); // 100 chars
        $element = ($this->mockElement)($maxAnchor, $errors);

        $field->validateAnchorId($element);

        expect($errors)->toBeEmpty();
    });
});
