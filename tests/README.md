# Tests — Matrix Block Anchor

Integration tests for the Matrix Block Anchor plugin, using [craft-pest](https://craftpest.com/).

## Requirements

Tests run inside the parent Craft project, which provides the full Craft + database context.

- PHP ≥ 8.2
- The parent project must have `markhuot/craft-pest-core` installed
- The **Matrix Block Anchor** plugin must be installed in the parent Craft project

## Running tests

From the **parent project root**:

```bash
ddev exec vendor/bin/pest plugins/matrix-block-anchor/tests \
  --test-directory=plugins/matrix-block-anchor/tests
```

To run a single file:

```bash
ddev exec vendor/bin/pest plugins/matrix-block-anchor/tests/Integration/SettingsModelTest.php \
  --test-directory=plugins/matrix-block-anchor/tests
```

## Test coverage

### `SettingsModelTest.php`

Tests the `Settings` model (`src/models/Settings.php`):

| Area | Cases |
|------|-------|
| Default values | Correct defaults for all three properties |
| `anchorPrefix` validation | Valid prefix; prefix starting with digit; prefix containing spaces or tabs; empty prefix |
| Boolean fields | `allowCustomAnchors` and `useLegacySeparator` accept boolean values |

### `MatrixBlockAnchorFieldTest.php`

Tests the `MatrixBlockAnchorField` field type (`src/fields/MatrixBlockAnchorField.php`):

| Area | Cases |
|------|-------|
| Metadata | `displayName()`, `getElementValidationRules()` |
| `normalizeValue()` — auto-generation | Null element returns prefix; stored value ignored when custom anchors disabled; result starts with configured prefix |
| `normalizeValue()` — custom anchors | Stored value used; leading `#` stripped; empty value falls back to auto-generation |
| Format validation | Valid anchors pass; invalid cases: starts with digit, contains space/tab, contains special characters or dots, exceeds 100-character limit; exact 100-character length accepted |

### `AnchorUniquenessTest.php`

Integration tests for the uniqueness validation in `MatrixBlockAnchorField`. Requires a live Craft + database context — each test builds a real section, matrix field, block entry type, and anchor field via factories, then asserts on `saveElement` results.

| Area | Cases |
|------|-------|
| Single block | Entry with one block saves without errors |
| Distinct anchors | Two blocks with different anchors save without errors |
| Duplicate blocked | Adding a block whose anchor matches an existing block on the same entry fails with an error on the anchor field |
| Unique accepted | Adding a block with a fresh anchor to an existing entry succeeds |
| Resave unchanged | Resaving an existing block without changing its anchor does not trigger a uniqueness error |

## How the mocks work

Format validation tests use a `PHPUnit\Framework\MockObject` of `craft\base\ElementInterface`. Critically, this mock is **not** a `NestedElementInterface`, so `validateUniqueAnchor()` returns early — only the format rules run. This keeps the tests focused and fast without needing a real matrix block hierarchy.

`addError()` calls on the mock are captured into a plain array so tests can assert on which field received an error without inspecting Craft's error bag.

The mock factory is stored in `$this->mockElement` via `beforeEach`. Because `createMock()` is `protected` on `PHPUnit\Framework\TestCase`, the factory is a closure bound to the TestCase instance using `Closure::bind($factory, $this, \PHPUnit\Framework\TestCase::class)`.

The mock targets `craft\base\Element` (the abstract base class) rather than `ElementInterface`. PHPUnit can only configure methods that are declared on the type being mocked, and `addError()` is inherited from `yii\base\Model` — it is not part of the interface. `craft\base\Element` does not implement `NestedElementInterface`, so the `validateUniqueAnchor()` guard still fires and returns early.
