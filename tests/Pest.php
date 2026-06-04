<?php

/**
 * Integration tests require a running Craft context.
 * Run from the parent Craft project:
 *
 *   vendor/bin/pest plugins/matrix-block-anchor/tests \
 *     --test-directory=plugins/matrix-block-anchor/tests
 */

// Apply TestCase + RefreshesDatabase to every Integration test.
// RefreshesDatabase wraps each test in a DB transaction that rolls back on
// teardown, keeping the database clean between tests.
use markhuot\craftpest\test\RefreshesDatabase;
use markhuot\craftpest\test\TestCase;

uses(
    TestCase::class,
    RefreshesDatabase::class,
)->in('Integration');
