<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public string $anchorPrefix = 'blockIdAnchor';
    public bool $allowCustomAnchors = false;
    public bool $useLegacySeparator = false;

    public function defineRules(): array
    {
        return [
            ['anchorPrefix', 'required'],
            ['anchorPrefix', 'string'],
            ['anchorPrefix', 'validateAnchorPrefix'],
            [['allowCustomAnchors', 'useLegacySeparator'], 'boolean'],
        ];
    }

    public function validateAnchorPrefix(string $attribute): void
    {
        $value = $this->$attribute;

        if (empty($value)) {
            return;
        }

        if (preg_match('/^\d/', $value)) {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix cannot start with a number.'));
        }

        if (preg_match('/\s/', $value)) {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix must not contain whitespaces (spaces, tabs, etc.).'));
        }

    }
}
