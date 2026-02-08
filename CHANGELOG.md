# Release Notes for Matrix Block Anchor

## 3.1.0 - 2026-01-08

### Changed
- Changed how anchors are returned in template. If using custom anchors, then any anchor field not filled in will default to the Anchor Prefix from settings. ([#5](https://github.com/john-henry/matrix-block-anchor/issues/5))
- Changed some field labels in settings to be more descriptive and concise
- Made all text translatable
- Updated README.md

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

### Migration Notes
If upgrading from v2.x or earlier and you have existing anchors using the format `#blockIdAnchor-123`, enable the "Use Legacy Separator" setting in the plugin configuration to maintain the same format and prevent broken links.

## 2.0.1 - 2024-09-09

### Added

- Craft 5 support.
- Github Workflow files

## 1.0.0
- Initial release
