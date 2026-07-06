<?php

use craft\elements\Entry;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use markhuot\craftpest\factories\Entry as EntryFactory;
use markhuot\craftpest\factories\EntryType as EntryTypeFactory;
use markhuot\craftpest\factories\Field as FieldFactory;
use markhuot\craftpest\factories\MatrixField as MatrixFieldFactory;
use markhuot\craftpest\factories\Section as SectionFactory;

// ---------------------------------------------------------------------------
// Skip guard — the multi-site tests below depend on a second, non-primary
// site being configured (see config/project/sites/). Without it these tests
// would report a false pass/fail that doesn't reflect the scoping behavior
// under test.
// ---------------------------------------------------------------------------

function secondSite(): ?\craft\models\Site
{
    $primary = \Craft::$app->getSites()->getPrimarySite();

    foreach (\Craft::$app->getSites()->getAllSites() as $site) {
        if ($site->id !== $primary->id) {
            return $site;
        }
    }

    return null;
}

// ---------------------------------------------------------------------------
// Helper — saves a new matrix block on an existing entry via saveElement so
// callers can inspect the return value and any validation errors.
// ---------------------------------------------------------------------------

function saveBlock(Entry $owner, int $fieldId, int $typeId, string $anchorHandle, string $anchorValue): Entry
{
    $block = new Entry();
    $block->fieldId = $fieldId;
    $block->typeId = $typeId;
    $block->ownerId = $owner->id;
    $block->siteId = $owner->siteId;
    $block->setFieldValue($anchorHandle, $anchorValue);

    \Craft::$app->getElements()->saveElement($block);

    return $block;
}

// ---------------------------------------------------------------------------
// Uniqueness tests
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField uniqueness validation', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = true;

        // Build: anchor field → block entry type → matrix field → section
        $anchorFieldFactory = FieldFactory::factory()->type(MatrixBlockAnchorField::class);

        $blockTypeFactory = EntryTypeFactory::factory()
            ->hasTitleField(false)
            ->fields($anchorFieldFactory);

        $matrixFieldFactory = MatrixFieldFactory::factory()
            ->entryTypes($blockTypeFactory);

        $this->matrixField = $matrixFieldFactory->create();

        $this->section = SectionFactory::factory()
            ->fields($this->matrixField)
            ->create();

        $this->blockType = $blockTypeFactory->getMadeModels()->first();
        $this->anchorField = $anchorFieldFactory->getMadeModels()->first();
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    // -----------------------------------------------------------------------

    it('saves a block with a custom anchor successfully', function() {
        $entry = EntryFactory::factory()->section($this->section->handle)->create();

        $block = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');

        expect($block->id)->not->toBeNull()
            ->and($block->hasErrors($this->anchorField->handle))->toBeFalse();
    });

    it('accepts a second block with a different anchor on the same entry', function() {
        $entry = EntryFactory::factory()->section($this->section->handle)->create();

        $first = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');
        $second = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'contact');

        expect($first->hasErrors($this->anchorField->handle))->toBeFalse()
            ->and($second->hasErrors($this->anchorField->handle))->toBeFalse();
    });

    it('rejects updating a block to use an anchor already taken on the same entry', function() {
        $entry = EntryFactory::factory()->section($this->section->handle)->create();
        $anchorHandle = $this->anchorField->handle;

        $blockA = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $anchorHandle, 'about-us');
        $blockB = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $anchorHandle, 'contact');

        expect($blockA->id)->not->toBeNull()
            ->and($blockB->id)->not->toBeNull();

        // Update block B to steal block A's anchor
        $blockB->setFieldValue($anchorHandle, 'about-us');
        $result = \Craft::$app->elements->saveElement($blockB);

        expect($result)->toBeFalse()
            ->and($blockB->hasErrors($anchorHandle))->toBeTrue();
    });

    it('does not raise a uniqueness error when resaving a block with an unchanged anchor', function() {
        $entry = EntryFactory::factory()->section($this->section->handle)->create();

        $block = saveBlock($entry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');

        // Resave without touching the anchor — the canonical-check bypass should fire.
        $result = \Craft::$app->getElements()->saveElement($block);

        expect($result)->toBeTrue()
            ->and($block->hasErrors($this->anchorField->handle))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// Multi-site propagation
//
// hasDuplicateAnchorInOwner() calls Entry::find()->primaryOwner($canonicalOwner),
// and NestedElementQueryTrait::primaryOwner() sets $this->siteId to the owner's
// own siteId. That locks the duplicate-check query to a single site: the one the
// owner element was loaded in. These tests pin down that per-site-scoped behaviour
// so a future change to it shows up as a failing test.
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField uniqueness validation — multi-site propagation', function() {
    beforeEach(function() {
        $this->secondSite = secondSite();

        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = true;

        $anchorFieldFactory = FieldFactory::factory()->type(MatrixBlockAnchorField::class);

        $blockTypeFactory = EntryTypeFactory::factory()
            ->hasTitleField(false)
            ->fields($anchorFieldFactory);

        $matrixFieldFactory = MatrixFieldFactory::factory()
            ->entryTypes($blockTypeFactory);

        $this->matrixField = $matrixFieldFactory->create();

        // Section factory propagates to all configured sites automatically.
        $this->section = SectionFactory::factory()
            ->fields($this->matrixField)
            ->create();

        $this->blockType = $blockTypeFactory->getMadeModels()->first();
        $this->anchorField = $anchorFieldFactory->getMadeModels()->first();
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    // -----------------------------------------------------------------------

    it('rejects the same anchor on the second site\'s propagated instance of the same owner entry', function() {
        if (!$this->secondSite) {
            $this->markTestSkipped('No second site configured — see config/project/sites/.');
        }

        $primaryEntry = EntryFactory::factory()->section($this->section->handle)->create();

        // Save a block with anchor "about-us" on the primary-site entry.
        saveBlock($primaryEntry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');

        // Load the same owner entry's propagated instance in the second site.
        // Because this Matrix field uses the default "all sites" propagation, the
        // block above is the same underlying block propagated here too, so
        // canonicalOwner resolution still finds it and a second block with the same
        // anchor on the propagated instance is rejected. In other words, loading the
        // owner through a different site doesn't get you past the uniqueness check.
        $secondSiteEntry = Entry::find()
            ->id($primaryEntry->id)
            ->siteId($this->secondSite->id)
            ->status(null)
            ->one();

        expect($secondSiteEntry)->not->toBeNull();

        $secondSiteBlock = saveBlock($secondSiteEntry, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');

        expect($secondSiteBlock->hasErrors($this->anchorField->handle))->toBeTrue();
    });

    it('allows the same anchor on two independent owner entries regardless of site', function() {
        if (!$this->secondSite) {
            $this->markTestSkipped('No second site configured — see config/project/sites/.');
        }

        // Two distinct owner entries (not propagated instances of the same entry) —
        // uniqueness is scoped per-owner, so the same anchor on a different owner
        // is unaffected, whether or not that owner happens to also exist in another site.
        $entryA = EntryFactory::factory()->section($this->section->handle)->create();
        $entryB = EntryFactory::factory()->section($this->section->handle)->create();

        $blockA = saveBlock($entryA, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');
        $blockB = saveBlock($entryB, $this->matrixField->id, $this->blockType->id, $this->anchorField->handle, 'about-us');

        expect($blockA->hasErrors($this->anchorField->handle))->toBeFalse()
            ->and($blockB->hasErrors($this->anchorField->handle))->toBeFalse();
    });
});
