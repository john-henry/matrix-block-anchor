# Matrix Block Anchor

A Craft CMS field that enables content editors to create anchor links directly to Matrix Blocks without needing developer assistance. It automatically retrieves the Matrix Block ID, allowing editors to easily copy the link and use it anywhere.

## Requirements

This plugin requires Craft CMS 5.0.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Matrix Block Anchor”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project

# tell Composer to load the plugin
composer require johnhenry/matrix-block-anchor

# tell Craft to install the plugin
./craft plugin/install matrix-block-anchor
```

## Configuring Matrix Block Anchors

The plugin offers several configuration options in the Matrix Block Anchor plugin settings:

### Custom Anchor Settings

The plugin includes an "Allow Custom Anchors" setting that enables content editors to create custom anchor IDs instead of using auto-generated ones. This is useful for:
- Creating meaningful, human-readable anchor links
- Improving SEO with descriptive anchor text
- Maintaining consistent anchor links even if content is reordered

When custom anchors are enabled:
- Anchor IDs cannot start with a number
- Anchor IDs cannot contain spaces
- Each anchor must be unique within the same entry

### Anchor Prefix

The default prefix for anchor IDs is `blockIdAnchor`, which can be customized in the plugin settings. In most cases, this default setting works well. A prefix is required as an ID cannot start with a number (block IDs start with a number).


### Legacy Separator Setting

The "Use Legacy Separator" setting provides backward compatibility for users upgrading from older plugin versions (prior to v3.0.0).

**For new installations:** Leave this disabled (default). Anchors with a prefix will generate cleanly as `#blockIdAnchor123` instead of `#blockIdAnchor-123`.

**For existing installations upgrading from v2.x or earlier:** If you have existing anchors in the format `#blockIdAnchor-123` (with a leading dash when no prefix was set), enable this setting to maintain the same format and prevent broken links.


## Using Matrix Block Anchors

Create a new field and choose Matrix Block Anchor as field type. Add this new field to each Matrix block where you want an anchor.

### Auto-Generated Anchors

By default, anchors are automatically generated using the block ID. With the default prefix `blockIdAnchor`, an anchor link will appear in the control panel as `#blockIdAnchor424242`.


### Custom Anchors

When "Allow Custom Anchors" is enabled in the field settings, content editors can enter their own anchor IDs. For example:
- `#about-us`
- `#pricing-section`
- `#contact-form`

Custom anchors are validated to ensure they:
- Contain at least one character
- Don't start with a number
- Don't contain spaces
- Are unique within the entry

If a custom anchor is not used in a Matrix block then it will default to the auto-generated anchor.

### Using in templates

In a typical Matrix page builder setup, use the field handle directly as the ID value:

```twig
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" id="{{ block.yourFieldHandle }}">
```

Note: Replace `yourFieldHandle` with the actual handle of your Matrix Block Anchor field.

### Matrix Page Builder Resources

- https://www.youtube.com/watch?v=rw3kNg4CF1g
- https://craftquest.io/courses/real-world-craft-cms-5/197080


## Sticky Header

Clicking on anchor links (links within the same page) causes the page to scroll. If the scroll is upwards, the linked element might get hidden under the header.

To prevent this, you can define scroll-padding in your CSS:

```
html,
body {
    scroll-padding-top: 100px; /* set to the height of your header */
}
```
