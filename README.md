# Matrix Block Anchor

A Craft CMS field that enables content editors to create anchor links directly to Matrix Blocks without needing developer assistance. It automatically retrieves the Matrix Block ID, allowing editors to easily copy the link and use it anywhere.

## Requirements

This plugin requires Craft CMS 4.0.0 or later, and PHP 8.0 or later.

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

The default prefix for anchor IDs is `blockIdAnchor`, which can be customized in the Matrix Block Anchor plugin settings. In most cases, this default setting works well.

## Using Matrix Block Anchors

Create a new field and choose Matrix Block Anchor as field type.Add this new field to each Matrix block where you want an anchor.

The default anchor prefix is `blockIdAnchor`, which will generate an anchor link in the control panel, such as `#blockIdAnchor-424242`.

In a typical Matrix page builder setup, simply add an ID to your block's parent div or section. Make sure to update the prefix to match what you've set in the plugin settings and seperate with a hyphen from the `block.id` variable.



```
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" id="blockIdAnchor-{{ block.id }}">
```
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
