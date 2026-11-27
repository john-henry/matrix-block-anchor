# Accessibility statement for Matrix Block Anchor

Matrix Block Anchor is a plugin for Craft CMS 5 and 6. This statement sets out how accessible it is, what I know is wrong with it, and how to tell me about anything else.

## What this covers

This statement covers Matrix Block Anchor 3.5.0:

- the Matrix Block Anchor field, including its copy button
- the plugin settings page

It doesn't cover:

- Craft's own control panel, which Craft reports on at https://craftcms.com/accessibility
- your site's own templates and content

## The standard I'm working to

WCAG 2.2 level AA. Matrix Block Anchor adds to the Craft control panel, which is an authoring tool, so I also follow ATAG 2.0 Part A where it applies to the parts Matrix Block Anchor adds. Craft itself works to the same standards and publishes its own reports at https://craftcms.com/accessibility, but those reports don't cover plugins.

## How far it meets it

The September review of 3.4.0 found no issues against WCAG 2.2 AA. In 3.5.0 the anchor field changed to look like Craft's own copy field and the Field Usage list on the settings page was regrouped, and neither change has been reviewed yet. Nothing has been tested with assistive technology yet either, so I'm not claiming full conformance until both are done.

## How it was checked

Last checked in September 2026:

- A review of every control panel template, script and stylesheet against WCAG 2.2 AA, including the WAI-ARIA Authoring Practices for any custom widgets, and contrast ratios worked out from the actual colours in the CSS.
- Not done yet: testing with screen readers (NVDA, JAWS and VoiceOver), zoom and reflow at 400%, or speech input. This statement will be updated once that's done.

## Known issues

None known right now. If you come across one, please tell me (see below).

## Tell me about a problem

If something in Matrix Block Anchor is hard or impossible for you to use, open an issue at https://github.com/john-henry/matrix-block-anchor/issues and put "Accessibility" in the title. Say which screen you were on, what you were trying to do, and which browser and assistive technology you use, if any. I aim to reply within five working days.

## Last reviewed

27 September 2026, for version 3.4.0. The changes in 3.5.0 haven't been reviewed yet.
