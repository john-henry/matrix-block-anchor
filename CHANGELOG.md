# Release Notes for Matrix Block Anchor

## 3.4.0 - 2026-09-25

### Added
- The anchor prefix can be set to an environment variable.
- Anchors can be copied from revisions, and by people who can only view an entry.
- The copy button works on a control panel served over plain http.

### Changed
- A custom anchor with characters it can't use now shows an error instead of being quietly tidied up.
- Blocks without a custom anchor no longer store one, so a duplicated block gets its own anchor.
- The anchor prefix must start with a letter, use only letters, numbers, hyphens and underscores, and be 80 characters or fewer.
- Use Legacy Separator can be changed while custom anchors are on; it applies to blocks left without one.
- The package you install no longer includes the documentation site or the test suite.

### Fixed
- The anchor box can be reached with a keyboard again when custom anchors are off.
- Two blocks given the same custom anchor in one save are now caught.
- Blocks can swap anchors, or take one from a block removed in the same save.
- Entries with lots of blocks save much faster.
- An unset environment variable no longer switches Allow Custom Anchors or Use Legacy Separator on.
- A prefix with characters an anchor can't use no longer stops entries from saving.
- The anchor box is labelled for screen readers, and its error shows under the field again.
- The plugin's styles no longer change copy fields elsewhere in the control panel.
- Field Usage lists an entry type under every Matrix field that uses it.
- The copy messages can now be translated.

## 3.3.1 - 2026-09-07

### Fixed
- Saving an entry no longer fails after a Matrix block is changed to an entry type that has the anchor field. ([#7](https://github.com/john-henry/matrix-block-anchor/issues/7))

## 3.3.0 - 2026-07-05

### Fixed
- An anchor that comes through in an unexpected shape falls back to the auto anchor instead of causing a server error.
- Anchors saved by imports and console commands are cleaned up the same as ones typed in the control panel.
- Each block's copy button works on its own, even straight after another one is used.
- The copy control is a proper button, so it works with a keyboard and screen readers.
- Copying an anchor is announced to screen readers.
- The copy button has a visible focus outline.

## 3.2.0 - 2026-06-04

### Added
- Anchors show in card previews.

### Changed
- New branding.
- The plugin settings page has clearer wording.

### Fixed
- The duplicate anchor error no longer shows for Matrix fields in card view. ([#6](https://github.com/john-henry/matrix-block-anchor/issues/6))

## 3.1.2 - 2026-03-15

### Fixed
- The duplicate anchor error no longer shows for Matrix fields in card view. ([#6](https://github.com/john-henry/matrix-block-anchor/issues/6))

## 3.1.1 - 2026-01-08

### Added
- A documentation link.

## 3.1.0 - 2026-01-08

### Added
- The plugin settings list where the anchor field is used.

### Changed
- With custom anchors on, a block left without one uses the auto anchor. ([#5](https://github.com/john-henry/matrix-block-anchor/issues/5))
- Clearer labels on the settings page.
- All text can be translated.

## 3.0.0 - 2025-11-09

> [!NOTE]
> If you're upgrading from 2.x and your anchors look like `#blockIdAnchor-123`, turn on Use Legacy Separator so existing links keep working.

### Added
- Custom anchors, with the Allow Custom Anchors setting.
- Custom anchors must start with a letter, can't contain spaces, and must be unique on the entry.
- The Use Legacy Separator setting.
- Translation support.

### Changed
- Auto anchors no longer have a dash between the prefix and the block ID.

## 2.0.1 - 2024-09-09

### Added
- Craft 5 support.

## 1.0.0 - 2024-08-19

### Added
- Initial release.
