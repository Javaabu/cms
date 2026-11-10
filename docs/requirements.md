---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- A database Laravel supports (MySQL 8, PostgreSQL, SQLite)

## Packages it installs

| Package | Version | For |
| --- | --- | --- |
| javaabu/post-types | ^1.0 | Pages, news, categories, tags |
| javaabu/widgets | ^1.0 | Widget locations and the widgets admin |
| javaabu/dynamic-menubuilder | ^1.0 | Menu locations and the menu builder |
| javaabu/settings | ^2.0 | The "Site" settings page |
| javaabu/mediapicker | ^1.0 | The media library (spatie/laravel-medialibrary) |
| javaabu/block-editor | ^1.0 | Page and news content |
| javaabu/permissions | ^3.0 | Permissions, roles and policies (spatie/laravel-permission) |
| javaabu/forms, javaabu/stencil, javaabu/manifest | ^2.0, ^1.0, ^1.0 | Blade views, forms, and shipping the React code |

Until these are on Packagist, add their GitHub repositories as VCS repositories (see this package's `composer.json`).

The CMS never depends on `javaabu/helpers` or `javaabu/settings` v1.

## Optional

| Package | For |
| --- | --- |
| `inertiajs/inertia-laravel` ^2.0 | The Inertia React pages (`cms.views` = `inertia`) |
| `javaabu/translatable` ^3.0 | Content, menus, widgets and settings in several languages, and a language switcher |

## For React

With `cms.views` set to `inertia`, the app builds the pages with Vite and [javaabu/manifest](https://github.com/Javaabu/manifest)'s plugin, and needs `react` and `react-dom` 19, `@inertiajs/react` 2 or 3, `@javaabu/forms` and `@javaabu/onu`. `php artisan manifest:check` lists what's missing. A Blade-only app needs no npm packages for the CMS: these are declared for the Inertia pages only, so `manifest:check` doesn't ask for them unless the app uses Inertia.
