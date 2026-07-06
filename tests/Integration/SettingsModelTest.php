<?php

use johnhenry\matrixblockanchor\models\Settings;

// ---------------------------------------------------------------------------
// Defaults
// ---------------------------------------------------------------------------

describe('Settings defaults', function() {
    it('has the expected default values', function() {
        $settings = new Settings();

        expect($settings->anchorPrefix)->toBe('blockIdAnchor')
            ->and($settings->allowCustomAnchors)->toBeFalse()
            ->and($settings->useLegacySeparator)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// anchorPrefix validation
// ---------------------------------------------------------------------------

describe('Settings::anchorPrefix validation', function() {
    it('passes with a valid prefix', function() {
        $settings = new Settings(['anchorPrefix' => 'blockId']);

        expect($settings->validate())->toBeTrue()
            ->and($settings->getErrors('anchorPrefix'))->toBeEmpty();
    });

    it('passes with a prefix containing letters and numbers', function() {
        $settings = new Settings(['anchorPrefix' => 'block123Anchor']);

        expect($settings->validate())->toBeTrue();
    });

    it('passes with a prefix containing hyphens and underscores', function() {
        $settings = new Settings(['anchorPrefix' => 'block-id_anchor']);

        expect($settings->validate())->toBeTrue();
    });

    it('fails when the prefix is empty', function() {
        $settings = new Settings(['anchorPrefix' => '']);

        expect($settings->validate())->toBeFalse()
            ->and($settings->getErrors('anchorPrefix'))->not->toBeEmpty();
    });

    it('fails when the prefix starts with a digit', function() {
        $settings = new Settings(['anchorPrefix' => '1block']);

        expect($settings->validate())->toBeFalse()
            ->and($settings->getErrors('anchorPrefix'))->not->toBeEmpty();
    });

    it('fails when the prefix contains a space', function() {
        $settings = new Settings(['anchorPrefix' => 'block id']);

        expect($settings->validate())->toBeFalse()
            ->and($settings->getErrors('anchorPrefix'))->not->toBeEmpty();
    });

    it('fails when the prefix contains a tab', function() {
        $settings = new Settings(['anchorPrefix' => "block\tid"]);

        expect($settings->validate())->toBeFalse()
            ->and($settings->getErrors('anchorPrefix'))->not->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// Boolean field validation
// ---------------------------------------------------------------------------

describe('Settings boolean fields', function() {
    it('accepts true for allowCustomAnchors', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => true]);

        expect($settings->validate())->toBeTrue();
    });

    it('accepts false for useLegacySeparator', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'useLegacySeparator' => false]);

        expect($settings->validate())->toBeTrue();
    });

    it('accepts null for allowCustomAnchors', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => null]);

        expect($settings->validate())->toBeTrue();
    });

    it('accepts an env var string for allowCustomAnchors', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => '$MY_ENV_VAR']);

        expect($settings->validate())->toBeTrue();
    });

    it('accepts an env var string for useLegacySeparator', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'useLegacySeparator' => '$MY_ENV_VAR']);

        expect($settings->validate())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Getters
// ---------------------------------------------------------------------------

describe('Settings getters', function() {
    it('getAllowCustomAnchors returns a bool when parse is true', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => true]);

        expect($settings->getAllowCustomAnchors(true))->toBeBool()->toBeTrue();
    });

    it('getAllowCustomAnchors returns the raw value when parse is false', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => true]);

        expect($settings->getAllowCustomAnchors(false))->toBeTrue();
    });

    it('getAllowCustomAnchors returns false when value is null', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => null]);

        expect($settings->getAllowCustomAnchors(true))->toBeFalse();
    });

    it('getUseLegacySeparator returns a bool when parse is true', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'useLegacySeparator' => true]);

        expect($settings->getUseLegacySeparator(true))->toBeBool()->toBeTrue();
    });

    it('getUseLegacySeparator returns false when value is null', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'useLegacySeparator' => null]);

        expect($settings->getUseLegacySeparator(true))->toBeFalse();
    });

    it('getUseLegacySeparator returns the raw env-var string when parse is false', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'useLegacySeparator' => '$MY_ENV_VAR']);

        expect($settings->getUseLegacySeparator(false))->toBe('$MY_ENV_VAR');
    });

    it('getAllowCustomAnchors returns the raw env-var string when parse is false', function() {
        $settings = new Settings(['anchorPrefix' => 'block', 'allowCustomAnchors' => '$MY_ENV_VAR']);

        expect($settings->getAllowCustomAnchors(false))->toBe('$MY_ENV_VAR');
    });
});
