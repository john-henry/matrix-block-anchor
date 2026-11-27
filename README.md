[![Stable Version](https://img.shields.io/packagist/v/johnhenry/matrix-block-anchor?label=stable&style=for-the-badge)](https://packagist.org/packages/johnhenry/matrix-block-anchor)
[![Static Badge](https://img.shields.io/badge/free-plugin?style=for-the-badge&logo=craftcms&logoColor=white&logoSize=auto&label=Craft%20Plugin%20Store&labelColor=%23E5422B)](https://plugins.craftcms.com/matrix-block-anchor?craft5)

![Matrix Block Anchor for Craft CMS](https://johnhenry.ie/images/plugins/promos/matrix-block-anchor/1.png)

# Matrix Block Anchor for Craft CMS

Matrix Block Anchor gives your editors anchor links straight to Matrix blocks. It builds each anchor from the block's ID and puts a copy button beside it. A developer adds the anchor to the block's template once, and from then on editors look after the rest themselves.

## Features

- Every block gets an anchor built from its ID, so there's nothing for editors to fill in.
- If you allow it, editors can type their own readable anchors, like `#about-us` or `#pricing`.
- The anchor belongs to the block, not to where it sits on the page, so moving blocks around doesn't break links.
- The prefix is `blockIdAnchor` by default, and you can change it to suit your own naming.
- Custom anchors are checked on save: they have to start with a letter, use only letters, numbers, hyphens and underscores, and be unique among the blocks they sit alongside (nested blocks are only checked against the other blocks in the same parent).
- A developer adds the field to the block entry types and outputs it once in the template.
- The copy button puts the anchor on the clipboard, ready to paste into a link.
- If you're upgrading from 2.x, a setting keeps your old links working.

## Where it's handy

- Matrix page builders
- Long articles with a table of contents
- Single-page sites with section navigation
- Documentation and help centres
- Landing pages that link down to their own sections

## Documentation

Full documentation is at [johnhenry.ie/plugins/matrix-block-anchor/docs](https://johnhenry.ie/plugins/matrix-block-anchor/docs/getting-started/overview).

## Requirements

- Craft CMS 5.0 or later
- PHP 8.2 or later

Craft CMS 6 is supported from 3.5.0. Craft 6 is still in beta and needs PHP 8.5. The plugin doesn't pull in `craftcms/yii2-adapter` for you, because that would stop it installing on Craft 5, so a Craft 6 project has to require it itself:

```bash
composer require craftcms/yii2-adapter
```

## Accessibility

The [accessibility statement](https://github.com/john-henry/matrix-block-anchor/blob/craft-5/ACCESSIBILITY.md) says how accessible the plugin is, what's been checked, and how to report a problem.

## Support

Need a hand? Open an issue on the [GitHub Issues page](https://github.com/john-henry/matrix-block-anchor/issues).

## License

This plugin is free to use under the MIT License.

---

<a href="https://johnhenry.ie/plugins/" target="_blank">
    <img height="46" src="https://johnhenry.ie/images/plugins/logo.svg" alt="John Henry - Craft CMS Plugins">
</a>
