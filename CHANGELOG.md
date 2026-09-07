# Release Notes for Matrix Block Anchor

## 3.3.1 - 2026-09-07

### Fixed
- Fixed a server error when saving an entry after changing a Matrix block from an entry type without the anchor field to one that has it. The block saves as normal now instead of leaving the entry stuck. ([#7](https://github.com/john-henry/matrix-block-anchor/issues/7))

## 3.3.0 - 2026-07-05

### Fixed
- Saving a block no longer throws a server error if the anchor value comes through in an unexpected shape; it falls back to the auto-generated anchor instead.
- Anchors created through imports or other non-CP tools (Feed Me, console commands, and the like) are now cleaned up the same way as anchors typed directly into the field, so an invalid anchor can no longer get through.
- Fixed the copy button briefly stopping every other block's copy button from working after you'd used one - each block's button now works independently.
- The copy control is a proper button now, not a link styled to look like one, so keyboard users and screen readers get consistent behaviour.
- Copying an anchor now gets announced to screen readers, not just shown visually.
- Added a visible focus outline on the copy button for keyboard navigation.
- Large entries with hundreds of Matrix blocks now log a warning if the duplicate-anchor check can't scan all of them, so an uncaught duplicate on a very large entry is easier to track down.

## 3.2.0 - 2026-06-04

### Added
- Added card preview attribute
- Added some Pest testing

### Changed
- Added new branding
- Tidied up plugin settings page with better messaging
- Made plugin architecture consistent with my other plugins

### Fixed
- _**Really**_ fixed issue where unique anchor error was triggering for Card views in Matrix field. ([#6](https://github.com/john-henry/matrix-block-anchor/issues/6))

## 3.1.2 - 2026-03-15

### Fixed
- Fixed issue where unique anchor error was triggering for Card views in Matrix field. ([#6](https://github.com/john-henry/matrix-block-anchor/issues/6))

## 3.1.1 - 2026-01-08

### Added
- Added Documentation URL

## 3.1.0 - 2026-01-08

### Changed
- Changed how anchors are returned in template. If using custom anchors, then any anchor field not filled in will default to the Anchor Prefix from settings. ([#5](https://github.com/john-henry/matrix-block-anchor/issues/5))
- Changed some field labels in settings to be more descriptive and concise
- Made all text translatable
- Updated README

### Added
- Added section to plugin settings for listing Field Usage
- GitHub Workflow files for code quality


## 3.0.0 - 2025-11-09

### Added
- Added ability to add custom anchor text via new "Allow Custom Anchors" setting
- Added validation for custom anchor IDs (must not start with number or contain spaces)
- Added uniqueness validation to ensure no duplicate anchors within the same entry
- Added "Use Legacy Separator" setting for backward compatibility with older plugin versions
- Added translation support

### Changed
- Auto-generated anchors now don't include a uneditable separator dash

> [!NOTE]
> If upgrading from v2.x or earlier and you have existing anchors using the format `#blockIdAnchor-123`, enable the "Use Legacy Separator" setting in the plugin configuration to maintain the same format and prevent broken links.

## 2.0.1 - 2024-09-09

### Added

- Craft 5 support.
- Github Workflow files

## 1.0.0 - 2024-08-19
- Initial release
