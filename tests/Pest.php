<?php

/**
 * Integration tests require a running Craft context.
 * Run from the parent Craft project:
 *
 *   ddev exec composer test:mba
 */

// Apply TestCase + RefreshesDatabase to every Integration test.
// RefreshesDatabase wraps each test in a DB transaction that rolls back on
// teardown, keeping the database clean between tests.
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use markhuot\craftpest\test\RefreshesDatabase;
use markhuot\craftpest\test\TestCase;

// Every test starts from the default settings, whatever the dev config file and
// .env set, and gets them back afterwards.
uses(
    TestCase::class,
    RefreshesDatabase::class,
)
    ->beforeEach(function() {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->originalSettings = $settings->toArray();
        $settings->anchorPrefix = 'blockIdAnchor';
        $settings->allowCustomAnchors = false;
        $settings->useLegacySeparator = false;
        MatrixBlockAnchorField::clearStoredAnchors();
    })
    ->afterEach(function() {
        MatrixBlockAnchor::getInstance()->getSettings()->setAttributes($this->originalSettings, false);
        MatrixBlockAnchorField::clearStoredAnchors();
    })
    ->in('Integration');
