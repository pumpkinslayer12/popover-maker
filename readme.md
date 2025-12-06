# Popover Maker

**Contributors:** pumpkinslayer12

**Tags:** popover, modal, google-forms, form, popup

**Requires at least:** 6.0

**Tested up to:** 6.4

**Requires PHP:** 7.4

**Stable tag:** 1.0

**License:** GPLv3 or later

**License URI:** https://www.gnu.org/licenses/gpl-3.0.html

## Description

Popover Maker lets you create modal popovers that display embedded Google Forms (or any iframe content) to your visitors. Perfect for newsletter signups, surveys, feedback forms, and announcements.

### Features

- **Embedded Forms** - Display Google Forms in a clean modal popover
- **Display Rules** - Show on homepage, pages, posts, or everywhere
- **Scheduling** - Set start and end dates for time-limited campaigns
- **Priority System** - Control which popover displays when multiple are active
- **Cookie Dismissal** - Remember when users close the popover
- **Engagement Analytics** - Track views, engagement rate, and bounce rate
- **Featured Images** - Add an image alongside your form
- **Responsive** - Stacks vertically on mobile devices

## Installation

1. Upload the `popover-maker` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' screen
3. Go to **Popovers → Add New** to create your first popover

## Usage

1. Create a new Popover
2. Enter a title (for admin reference only)
3. Paste your Google Form embed URL in **Form Settings**
4. Optionally set a featured image
5. Configure display location, scheduling, and dismissal settings
6. Publish

## Frequently Asked Questions

### How do I get a Google Form embed URL?

In Google Forms, click Send → Embed icon (< >) → Copy the URL from the `src` attribute.

### Can I show different popovers on different pages?

Yes! Use the Display Location setting to target homepage, pages, posts, or all.

### How does priority work?

Higher priority numbers display first. If two popovers match the same page, the one with higher priority wins. Ties go to the most recently created.

## Changelog

### 1.0
- Initial release
- Google Forms embedding
- Display location rules
- Date scheduling
- Priority system
- Cookie-based dismissal
- Engagement analytics (views, engaged, bounced)
