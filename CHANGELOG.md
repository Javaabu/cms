# Changelog

All notable changes to `javaabu/cms` are documented in this file.

## 2.0.0 - Unreleased

A rebuild: the CMS is now assembled from javaabu/post-types, javaabu/widgets, javaabu/dynamic-menubuilder, javaabu/settings, javaabu/mediapicker, javaabu/block-editor and javaabu/permissions instead of reimplementing them. See [docs/upgrading.md](docs/upgrading.md).

- PHP 8.4+, Laravel 12 and 13.
- Default content model: pages, news, categories and tags; header and footer menus; sidebar and footer widgets.
- `Route::cmsAdmin()` and `Route::cms()` route macros.
- Admin dashboard and permission-aware navigation (`Cms::navigation()`).
- Public theme in Blade and Inertia React.
- `cms:install` and `cms:import-v1` commands.
- Optional javaabu/translatable support.
- No dependency on javaabu/helpers.
- The React layouts import the theme's stylesheet; npm packages are declared for the Inertia pages only (`npm(..., for: 'pages')`), so Blade apps aren't asked to install React.

## 1.1.0

- v1: custom post types, categories and tags in the CMS's own tables.
