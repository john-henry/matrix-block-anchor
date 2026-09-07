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
// Entry type switching
//
// A Matrix field can mix entry types that carry the anchor field with entry
// types that don't. Switching a block from a type without the field to a type
// with it happens in a (provisional) draft, so the draft block is a derivative
// whose canonical still has the old entry type and can't answer
// getFieldValue() for the anchor handle.
//
// The draft block below is built by hand rather than through the drafts
// service: the craft-pest section factory saves its entry type before the
// field layout, so the owner entry ends up with an empty layout and the real
// draft machinery never duplicates the blocks. Setting canonicalId on an
// unsaved block puts the validator in exactly the state the CP flow produces.
//
// Regression cover for https://github.com/john-henry/matrix-block-anchor/issues/7
// ---------------------------------------------------------------------------

describe('MatrixBlockAnchorField entry type switching', function() {
    beforeEach(function() {
        $this->settings = MatrixBlockAnchor::getInstance()->getSettings();
        $this->origAllowCustomAnchors = $this->settings->allowCustomAnchors;
        $this->settings->allowCustomAnchors = true;

        $anchorFieldFactory = FieldFactory::factory()->type(MatrixBlockAnchorField::class);

        // Type A carries no anchor field, type B does.
        $typeAFactory = EntryTypeFactory::factory()->hasTitleField(false);
        $typeBFactory = EntryTypeFactory::factory()
            ->hasTitleField(false)
            ->fields($anchorFieldFactory);

        $this->matrixField = MatrixFieldFactory::factory()
            ->entryTypes($typeAFactory, $typeBFactory)
            ->create();

        $this->section = SectionFactory::factory()
            ->fields($this->matrixField)
            ->create();

        $this->typeA = $typeAFactory->getMadeModels()->first();
        $this->typeB = $typeBFactory->getMadeModels()->first();
        $this->anchorField = $anchorFieldFactory->getMadeModels()->first();
    });

    afterEach(function() {
        $this->settings->allowCustomAnchors = $this->origAllowCustomAnchors;
    });

    // -----------------------------------------------------------------------

    it('validates a draft block switched from an entry type without the anchor field to one with it', function() {
        $entry = EntryFactory::factory()->section($this->section->handle)->create();

        // Publish a block of type A, which has no anchor field.
        $canonicalBlock = new Entry();
        $canonicalBlock->fieldId = $this->matrixField->id;
        $canonicalBlock->typeId = $this->typeA->id;
        $canonicalBlock->ownerId = $entry->id;
        $canonicalBlock->siteId = $entry->siteId;

        expect(\Craft::$app->getElements()->saveElement($canonicalBlock))->toBeTrue();

        // The draft version of that same block, now switched to type B.
        $draftBlock = new Entry();
        $draftBlock->fieldId = $this->matrixField->id;
        $draftBlock->typeId = $this->typeB->id;
        $draftBlock->ownerId = $entry->id;
        $draftBlock->siteId = $entry->siteId;
        $draftBlock->canonicalId = $canonicalBlock->id;
        $draftBlock->setFieldValue($this->anchorField->handle, 'about-us');

        expect($draftBlock->getIsCanonical())->toBeFalse();

        $this->anchorField->validateAnchorId($draftBlock);

        expect($draftBlock->hasErrors($this->anchorField->handle))->toBeFalse();
    });
});
