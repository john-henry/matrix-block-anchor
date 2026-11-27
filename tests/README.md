# Matrix Block Anchor tests

Integration tests for the Matrix Block Anchor plugin, using [craft-pest](https://craftpest.com/).

## Requirements

The tests run inside the parent Craft project, which provides Craft and the database.

- PHP 8.2 or later
- `markhuot/craft-pest-core` installed in the parent project
- The Matrix Block Anchor plugin installed in the parent project

## Running tests

From the parent project root:

```bash
ddev exec composer test:mba
```

To run a single file, keep the `--test-directory` flag. Without it the plugin's `Pest.php` is skipped and the run passes without testing anything:

```bash
ddev exec vendor/bin/pest plugins/matrix-block-anchor/tests/Integration/SettingsModelTest.php \
  --test-directory=plugins/matrix-block-anchor/tests
```

## What each file covers

| File | Covers |
|------|--------|
| `SettingsModelTest.php` | Settings defaults, prefix validation, the boolean settings and their getters |
| `MatrixBlockAnchorFieldTest.php` | Field metadata, previews, `normalizeValue()` for auto, legacy and custom anchors, cleaning of values that skip validation, format validation, and the Craft 6 `getElementRules()` path |
| `AnchorUniquenessTest.php` | Uniqueness on one entry, on drafts, and across sites |
| `OwnerSaveTest.php` | Saving blocks through the owner entry: duplicates in one save, swapped anchors, removed blocks, and blocks left blank |
| `EntryTypeSwitchTest.php` | A draft block switched to an entry type that has the anchor field |
| `AnchorInputRenderTest.php` | The input is read-only, not disabled, when custom anchors are off |
| `PluginBootTest.php` | The plugin trait is still wired to the main class |

## How the format validation mocks work

The format tests mock `craft\base\Element` rather than `ElementInterface`. PHPUnit can only configure methods declared on the mocked type, and `addError()` comes from `yii\base\Model`, not the interface.

`craft\base\Element` doesn't implement `NestedElementInterface`, so `_validateUniqueAnchor()` returns early and only the format rules run. That keeps these tests fast, with no real Matrix block to build.

`addError()` calls are captured into a plain array, so a test can check which field got an error without going through Craft's error bag.

`createMock()` is protected on `PHPUnit\Framework\TestCase`, so the mock factory set in `beforeEach` is a closure bound to the test case with `Closure::bind($factory, $this, \PHPUnit\Framework\TestCase::class)`.
