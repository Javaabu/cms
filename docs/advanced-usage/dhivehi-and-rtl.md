---
title: Dhivehi and RTL
sidebar_position: 5
---

## Strings

Every string the CMS shows is in `lang/en.json` and `lang/dv.json`: the layouts, the dashboard, the home page, the widgets, the site settings, the default post type, taxonomy and location labels, and the console output. Publish them to change them:

```bash
php artisan vendor:publish --tag=cms-translations
```

The React layouts get their strings from PHP (`cms.strings`), so they're translated too.

When you add post types, taxonomies or navigation links, add their labels to your app's `lang/dv.json`.

## Right to left

- The layouts set `dir` from the current language (`rtl` for Dhivehi), from javaabu/translatable when it's installed, else from `app.locale`.
- `cms.css`, the Blade views and the React components use logical properties only (`margin-inline-start`, `inset-inline-start`, `border-inline-end`), checked by the test suite.
- Titles, menu items and widget text have `dir="auto"`, so Thaana inside English pages (and the other way round) reads correctly.
- Under `:lang(dv)` the theme uses a Thaana font stack (`--cms-font-thaana`).

## Thaana input

The CMS has no forms of its own: post, term, menu, widget and settings forms come from their packages, which give Dhivehi text inputs Thaana keyboard support.

## For review

These translations should be checked by a Dhivehi speaker:

| English | Dhivehi |
| --- | --- |
| Appearance | ފެނުން |
| Administration | އިދާރީ |
| Dashboard | ޑޭޝްބޯޑް |
| Tagline | ޓެގްލައިން |
| Terms (a taxonomy's terms) | ބައިތައް |
| Pending review | ރިވިއުއަށް އިންތިޒާރުގައި |
| Skip to content | ކޮންޓެންޓަށް ދާށެވެ |
| View all | ހުރިހާ އެއްޗެއް ބައްލަވާ |
| About us (starter page) | އަޅުގަނޑުމެންނާ ބެހޭ |
| Welcome to your new site (starter news) | އާ ވެބްސައިޓަށް މަރުޙަބާ |
| © :year :site. All rights reserved. | © :year :site. ހުރިހާ ޙައްޤެއް ލިބިގެންވޭ. |
| Dry run: nothing was saved. | ޓެސްޓަކަށް ރަން ކުރީ: އެއްވެސް އެއްޗެއް ސޭވެއް ނުކުރެވޭ. |
