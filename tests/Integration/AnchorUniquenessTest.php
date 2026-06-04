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
