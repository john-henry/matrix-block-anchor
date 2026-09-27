<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\matrixblockanchor\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * Matrix Block Anchor settings model
 *
 * Stores and validates plugin configuration for the Matrix Block Anchor plugin,
 * including the anchor prefix, custom anchor support, and legacy separator mode.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class Settings extends Model
{
    // =========================================================================
    // Public Properties
    // =========================================================================

    /**
     * @var string The prefix prepended to all auto-generated anchor IDs
     * @since 1.0.0
     */
    public string $anchorPrefix = 'blockIdAnchor';

    /**
     * @var bool|string|null Whether editors may set a custom anchor on each block.
     *                       Accepts a boolean or an `$ENV_VAR` reference string.
     * @since 1.0.0
     */
    public bool|string|null $allowCustomAnchors = false;

    /**
     * @var bool|string|null Whether to use the legacy hyphen separator between prefix and block ID.
     *                       Accepts a boolean or an `$ENV_VAR` reference string.
     * @since 2.0.0
     */
    public bool|string|null $useLegacySeparator = false;

    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Returns the resolved boolean value of the allowCustomAnchors setting.
     *
     * When `$parse` is true, resolves environment variable references via
     * {@see App::parseBooleanEnv()}, treating a value that doesn't resolve as false.
     * When false, returns the raw stored value for settings form display.
     *
     * @param bool $parse Whether to resolve env var references
     * @return bool|string The resolved boolean, or the raw env-var string when unparsed
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getAllowCustomAnchors(bool $parse = true): bool|string
    {
        $value = $this->allowCustomAnchors ?? false;
        return $parse ? (App::parseBooleanEnv($value) ?? false) : $value;
    }

    /**
     * Returns the resolved boolean value of the useLegacySeparator setting.
     *
     * When `$parse` is true, resolves environment variable references via
     * {@see App::parseBooleanEnv()}, treating a value that doesn't resolve as false.
     * When false, returns the raw stored value for settings form display.
     *
     * @param bool $parse Whether to resolve env var references
     * @return bool|string The resolved boolean, or the raw env-var string when unparsed
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 2.0.0
     */
    public function getUseLegacySeparator(bool $parse = true): bool|string
    {
        $value = $this->useLegacySeparator ?? false;
        return $parse ? (App::parseBooleanEnv($value) ?? false) : $value;
    }

    /**
     * Returns the anchor prefix.
     *
     * When `$parse` is true, resolves an `$ENV_VAR` reference or alias via
     * {@see App::parseEnv()}. When false, returns the raw stored value for
     * settings form display.
     *
     * @param bool $parse Whether to resolve env var references
     * @return string The prefix
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 3.4.0
     */
    public function getAnchorPrefix(bool $parse = true): string
    {
        return $parse ? trim((string)App::parseEnv($this->anchorPrefix)) : $this->anchorPrefix;
    }

    /**
     * Validates that the resolved anchor prefix can start an HTML id: a letter,
     * then only letters, numbers, hyphens and underscores.
     *
     * @param string $attribute The attribute name being validated
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function validateAnchorPrefix(string $attribute): void
    {
        $value = $this->getAnchorPrefix();

        if ($value === '') {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix cannot be blank. If it’s an environment variable, check that it’s set.'));
            return;
        }

        if (preg_match('/^\d/', $value)) {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix cannot start with a number.'));
            return;
        }

        if (preg_match('/\s/', $value)) {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix must not contain whitespaces (spaces, tabs, etc.).'));
            return;
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $value)) {
            $this->addError($attribute, Craft::t('matrix-block-anchor', 'Anchor prefix can only contain letters, numbers, hyphens, and underscores, and must start with a letter.'));
        }
    }

    // =========================================================================
    // Protected Methods
    // =========================================================================

    /**
     * Returns the validation rules for settings.
     *
     * @return array<int, mixed> Yii validation rule definitions
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function defineRules(): array
    {
        return [
            ['anchorPrefix', 'required'],
            ['anchorPrefix', 'string', 'max' => 80],
            ['anchorPrefix', 'validateAnchorPrefix'],
            [['allowCustomAnchors', 'useLegacySeparator'], 'safe'],
        ];
    }
}
