# Release Notes for Matrix Block Anchor

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
