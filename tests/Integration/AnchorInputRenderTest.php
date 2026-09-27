<?php

/**
 * How the anchor input renders, and why it is read-only rather than disabled.
 *
 * The whole point of this field is that somebody copies the value out of it. A
 * disabled input is taken out of the tab order and announced as unavailable, so
 * the one thing the field exists for is the thing a keyboard or screen reader
 * user cannot do. Read-only keeps it reachable and selectable, and nothing is
 * lost by letting it post: normalizeValue() ignores a submitted value outright
 * when custom anchors are switched off.
 */

use craft\web\View;
use johnhenry\matrixblockanchor\fields\MatrixBlockAnchorField;
use johnhenry\matrixblockanchor\MatrixBlockAnchor;

beforeEach(function() {
    Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
});

describe('MatrixBlockAnchorField::getInputHtml()', function() {
    it('renders the input read-only, not disabled, when custom anchors are off', function() {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();
        $was = $settings->allowCustomAnchors;
        $settings->allowCustomAnchors = false;

        try {
            $html = (new MatrixBlockAnchorField(['handle' => 'anchor']))->getInputHtml('blockIdAnchor-1', null);
        } finally {
            $settings->allowCustomAnchors = $was;
        }

        expect($html)->toContain('readonly')
            ->and($html)->not->toContain('disabled');
    });

    it('leaves the input editable when custom anchors are on', function() {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();
        $was = $settings->allowCustomAnchors;
        $settings->allowCustomAnchors = true;

        try {
            $html = (new MatrixBlockAnchorField(['handle' => 'anchor']))->getInputHtml('blockIdAnchor-1', null);
        } finally {
            $settings->allowCustomAnchors = $was;
        }

        expect($html)->not->toContain('readonly')
            ->and($html)->not->toContain('disabled');
    });

    it('ignores a posted custom anchor when custom anchors are off', function() {
        $settings = MatrixBlockAnchor::getInstance()->getSettings();
        $was = $settings->allowCustomAnchors;
        $settings->allowCustomAnchors = false;

        try {
            $entry = \markhuot\craftpest\factories\Entry::factory()->create();
            $normalised = (new MatrixBlockAnchorField(['handle' => 'anchor']))
                ->normalizeValue('sneakyCustomValue', $entry);
        } finally {
            $settings->allowCustomAnchors = $was;
        }

        expect($normalised)->not->toContain('sneakyCustomValue');
    });
});
