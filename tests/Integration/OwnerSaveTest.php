<?php

/**
 * Anchors saved the way the control panel saves them: through the owner entry,
 * with Craft's Matrix field validating every block in the save together.
 *
 * Blocks in one save are checked against each other's new values, not the
 * stored ones, so two new blocks can't share an anchor and two blocks can swap.
 * Only typed anchors are stored, so blocks left blank keep an anchor built from
 * their own ID.
 */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;
use markhuot\craftpest\factories\Entry as EntryFactory;
use markhuot\craftpest\factories\EntryType as EntryTypeFactory;
use markhuot\craftpest\factories\Field as FieldFactory;
use markhuot\craftpest\factories\MatrixField as MatrixFieldFactory;
use markhuot\craftpest\factories\Section as SectionFactory;

beforeEach(function() {
    MatrixBlockAnchor::getInstance()->getSettings()->allowCustomAnchors = true;

    $anchorFieldFactory = FieldFactory::factory()->type(MatrixBlockAnchorField::class);
    $blockTypeFactory = EntryTypeFactory::factory()
        ->hasTitleField(false)
        ->fields($anchorFieldFactory);

    $this->matrixField = MatrixFieldFactory::factory()->entryTypes($blockTypeFactory)->create();
    $this->section = SectionFactory::factory()->fields($this->matrixField)->create();
    $this->blockType = $blockTypeFactory->getMadeModels()->first();
    $this->anchorHandle = $anchorFieldFactory->getMadeModels()->first()->handle;

    // The section factory leaves the owner's entry type with an empty layout,
    // so the Matrix field is put on it here.
    $this->owner = EntryFactory::factory()->section($this->section->handle)->create();
    $type = $this->owner->getType();
    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['layout' => $layout, 'name' => 'Content']);
    $tab->setElements([new CustomField($this->matrixField)]);
    $layout->setTabs([$tab]);
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type);

    $this->owner = Entry::find()->id($this->owner->id)->status(null)->one();
});

/**
 * Saves the owner with the given blocks, keyed `newN` for new blocks or by ID
 * for existing ones, in that order.
 *
 * @param array<string|int, string|null> $anchors
 */
function saveOwnerWithBlocks(Entry $owner, string $matrixHandle, string $typeHandle, string $anchorHandle, array $anchors): bool
{
    $entries = [];
    foreach ($anchors as $key => $anchor) {
        $entries[$key] = [
            'type' => $typeHandle,
            'fields' => [$anchorHandle => $anchor],
        ];
    }

    $owner->setFieldValue($matrixHandle, [
        'entries' => $entries,
        'sortOrder' => array_keys($anchors),
    ]);
    $owner->setScenario(\craft\base\Element::SCENARIO_LIVE);

    return Craft::$app->getElements()->saveElement($owner);
}

function blocksOf(Entry $owner): array
{
    return Entry::find()->primaryOwnerId($owner->id)->status(null)->orderBy('sortOrder')->all();
}

describe('Saving an entry with its blocks', function() {
    it('rejects two new blocks given the same anchor', function() {
        $saved = saveOwnerWithBlocks($this->owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            'new1' => 'faq',
            'new2' => 'faq',
        ]);

        $blocks = $this->owner->getFieldValue($this->matrixField->handle)->getCachedResult();
        $errors = array_map(fn(Entry $block) => $block->getErrors($this->anchorHandle), $blocks);

        expect($saved)->toBeFalse()
            ->and(json_encode($errors))->toContain('already used by another block');
    });

    it('flags only the block that took an anchor, not the one that already had it', function() {
        expect(saveOwnerWithBlocks($this->owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            'new1' => 'faq',
            'new2' => 'contact',
        ]))->toBeTrue();

        [$a, $b] = blocksOf($this->owner);
        $owner = Entry::find()->id($this->owner->id)->status(null)->one();

        expect(saveOwnerWithBlocks($owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            $a->id => 'faq',
            $b->id => 'faq',
        ]))->toBeFalse();

        $blocks = $owner->getFieldValue($this->matrixField->handle)->getCachedResult();
        $flagged = array_values(array_map(
            fn(Entry $block) => $block->id,
            array_filter($blocks, fn(Entry $block) => $block->hasErrors($this->anchorHandle)),
        ));

        expect($flagged)->toBe([$b->id]);
    });

    it('lets two blocks swap anchors', function() {
        expect(saveOwnerWithBlocks($this->owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            'new1' => 'intro',
            'new2' => 'summary',
        ]))->toBeTrue();

        [$a, $b] = blocksOf($this->owner);
        $owner = Entry::find()->id($this->owner->id)->status(null)->one();

        expect(saveOwnerWithBlocks($owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            $a->id => 'summary',
            $b->id => 'intro',
        ]))->toBeTrue();
    });

    it('lets an anchor move to another block when its block is removed', function() {
        expect(saveOwnerWithBlocks($this->owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            'new1' => 'pricing',
            'new2' => 'contact',
        ]))->toBeTrue();

        [, $b] = blocksOf($this->owner);
        $owner = Entry::find()->id($this->owner->id)->status(null)->one();

        expect(saveOwnerWithBlocks($owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            $b->id => 'pricing',
        ]))->toBeTrue();
    });

    it('gives blocks left blank their own auto anchor, and stores nothing for them', function() {
        expect(saveOwnerWithBlocks($this->owner, $this->matrixField->handle, $this->blockType->handle, $this->anchorHandle, [
            'new1' => '',
            'new2' => '',
        ]))->toBeTrue();

        [$a, $b] = blocksOf($this->owner);

        expect($a->getFieldValue($this->anchorHandle))->toBe('blockIdAnchor' . $a->id)
            ->and($b->getFieldValue($this->anchorHandle))->toBe('blockIdAnchor' . $b->id)
            ->and($a->getSerializedFieldValues()[$this->anchorHandle] ?? null)->toBeNull();
    });
});

describe('A stored anchor', function() {
    it('that has the shape of another block\'s auto anchor is read as this block\'s own', function() {
        $field = new MatrixBlockAnchorField(['handle' => 'anchor']);
        $block = new Entry(['id' => 42]);

        expect($field->normalizeValue('blockIdAnchor7', $block))->toBe('blockIdAnchor42')
            ->and($field->normalizeValue('blockIdAnchor', $block))->toBe('blockIdAnchor42');
    });
});

describe('A typed anchor', function() {
    it('is validated as typed, not silently cleaned', function() {
        $block = new Entry();
        $block->fieldId = $this->matrixField->id;
        $block->typeId = $this->blockType->id;
        $block->setOwner($this->owner);
        $block->setPrimaryOwner($this->owner);
        $block->siteId = $this->owner->siteId;
        $block->setFieldValueFromRequest($this->anchorHandle, '1 About Us!');

        expect($block->validate())->toBeFalse()
            ->and($block->getErrors($this->anchorHandle))->not->toBeEmpty();
    });

    it('may carry a leading hash and surrounding spaces', function() {
        $block = new Entry();
        $block->fieldId = $this->matrixField->id;
        $block->typeId = $this->blockType->id;
        $block->setOwner($this->owner);
        $block->setPrimaryOwner($this->owner);
        $block->siteId = $this->owner->siteId;
        $block->setFieldValueFromRequest($this->anchorHandle, '  #about-us ');

        expect($block->validate())->toBeTrue()
            ->and($block->getFieldValue($this->anchorHandle))->toBe('about-us');
    });
});
