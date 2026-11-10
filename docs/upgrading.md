---
title: Upgrading from v1
sidebar_position: 1.25
---

v2 is a rebuild. v1 kept posts, post types, categories and tags in its own tables, with its own models, controllers, admin views and jQuery/Editor.js assets. v2 assembles [javaabu/post-types](https://github.com/Javaabu/post-types), [javaabu/widgets](https://github.com/Javaabu/widgets), [javaabu/dynamic-menubuilder](https://github.com/Javaabu/dynamic-menubuilder) and others, and brings v1's content over with one command. Plan for a code change and a data migration.

## What changed

| v1 | v2 |
| --- | --- |
| PHP 8.2, Laravel 10–12 | PHP 8.4, Laravel 12–13 |
| Post types as rows in `post_types`, created by `cms:setup` | Post types defined in code with javaabu/post-types (`config('cms.post_types')`, `PostTypes::register()`) |
| `features` (`documents`, `image-gallery`, `document-number`, `expireable`, …) | `supports()` (content, excerpt, media, meta, author, scheduling), `->metaFields()` and `->media()` |
| Category types and categories (`category_types`, `categories`, nested set) | Taxonomies and terms (`post_terms`), hierarchical taxonomies |
| Tags (`tags`, `tag_model`) | The `tags` taxonomy |
| `Javaabu\Cms\Models\Post`, `PostType`, `Category`, `CategoryType`, `Tag` | `Javaabu\PostTypes\Models\Post` and `Term`, `PostType` and `Taxonomy` definitions |
| Editor.js content | javaabu/block-editor documents |
| `config('cms.web.controllers')`, `config('cms.admin.controllers')`, `Routes` helper, root slugs | `Route::cmsAdmin()`, `Route::cms()`, `config('cms.controllers')` and post-types' controllers |
| javaabu/menu-builder | javaabu/dynamic-menubuilder |
| Media through javaabu/mediapicker v0 and the CMS's own `Media` model | javaabu/mediapicker v1 (`Route::mediapicker()`) |
| Translations through javaabu/translatable v2, required | javaabu/translatable v3, optional |
| `CmsPermissionsSeeder` | Permissions registered in code; `php artisan permissions:sync` |
| Depends on javaabu/helpers | No javaabu/helpers |

## Steps

### 1. Back up

Back up the database and `storage/`. The import doesn't delete v1's tables, but the upgrade changes the schema.

### 2. Update Composer

```json
"require": {
    "php": "^8.4",
    "laravel/framework": "^12.0",
    "javaabu/cms": "^2.0"
}
```

Remove `javaabu/menu-builder`, `javaabu/helpers` and `mariuzzo/laravel-js-localization` if only the CMS needed them, then `composer update`.

### 3. Remove v1's code from the app

- Published v1 views (`resources/views/vendor/cms`), JS blocks (`resources/js/vendor/cms`) and the media Sass partials.
- Routes that used v1's controllers or `Javaabu\Cms\Support\Routes`.
- References to `Javaabu\Cms\Models\*`, `Javaabu\Cms\Enums\*`, `PostTypeFeatures`, `RootSlugsRegistrar` and the `cms` helpers.
- `config/cms.php`: v2's keys are different. Move it aside and publish v2's with `php artisan vendor:publish --tag=cms-config` (or let `cms:install` do it).

### 4. Define your post types

v1's default types were news, blog, downloads, announcements, publications, jobs, galleries, tenders, reports and pages. v2 registers `page` and `news`. List v1's types and where they'll go:

```bash
php artisan cms:import-v1 --dry-run
```

Define the ones you keep, for example:

```php
// app/Providers/AppServiceProvider.php
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\TextInput;

public function boot(): void
{
    PostTypes::taxonomy(Taxonomy::make('tender_categories')->labels('Tender category', 'Tender categories'));

    PostTypes::register(
        PostType::make('tenders')
            ->labels('Tender', 'Tenders')
            ->icon('gavel')
            ->supports(['content', 'excerpt', 'media', 'meta', 'scheduling'])
            ->taxonomies(['tender_categories'])
            ->metaFields([
                TextInput::make('document_no')->label(__('Document number')),
                TextInput::make('ref_no')->label(__('Reference number')),
                DateTimePicker::make('expire_at')->label(__('Closes at')),
            ])
            ->media([MediaPickerField::make('documents')->mediaType('document')->multiple()])
    );
}
```

v1's feature columns become meta with the same names: `document_no`, `expire_at`, `format`, `video_url`, `page_style`, `ref_no`, `coords`, `city_id` and `recently_updated`. Define meta fields with those names to edit them.

### 5. Migrate

```bash
php artisan migrate
```

Before post-types creates its `posts` table, a CMS migration finds v1's `posts` table (by its `menu_order` column) and renames v1's tables to `cms_v1_posts`, `cms_v1_post_types`, `cms_v1_categories`, `cms_v1_category_types`, `cms_v1_category_model`, `cms_v1_tags` and `cms_v1_tag_model`. New installs aren't touched. Turn it off with `cms.upgrade.rename_v1_tables`, or change the prefix with `cms.upgrade.prefix`.

### 6. Import

```bash
php artisan cms:import-v1 --dry-run          # see what happens, saves nothing
php artisan cms:import-v1
```

| Option | |
| --- | --- |
| `--type=v1:v2` | Put a v1 post type's posts in a v2 post type: `--type=blog:news`. Repeatable |
| `--taxonomy=v1:v2` | Put a v1 category type's categories in a v2 taxonomy: `--taxonomy=tender-categories:tender_categories`. Repeatable |
| `--dry-run` | Run inside a transaction that's rolled back |
| `--force` | Import even though `posts` already has posts (v1 ids won't be kept) |

What it does:

- **Post types.** A v1 type's posts go to the v2 type given with `--type`, else the type with the same key, its singular (`tenders` → `tender`), or `pages` → `page`. Posts of other types are left out and listed.
- **Posts.** Title (shortened to 255 characters, listed), slug (made unique), excerpt, content, status, publish date, timestamps and trashed posts. Ids are kept when `posts` is empty, so anything storing a post id keeps working.
- **Statuses.** `draft`, `pending` and `published` stay; `scheduled` becomes `published` with its future date (still scheduled); `rejected` and `archived` become `draft` unless the post type has those statuses.
- **Content.** Editor.js blocks become block-editor nodes: paragraphs (with bold, italic, underline, strike, code and links), headings, lists (nested), quotes, delimiters, tables, raw HTML, embeds, images, and columns (flattened). v1's action cards, related posts, document lists and statistics have no v2 block and are left out and counted. Plain HTML content becomes an HTML block.
- **Translations.** The `translations` and `lang` columns are copied, with translated content converted the same way.
- **Meta.** v1's feature columns, when set.
- **Categories.** Each category type's categories go to the taxonomy given with `--taxonomy`, else one with the same key (`news-categories` or `news_categories`), else `categories`. Nesting and order are kept; clashing slugs get `-2`.
- **Tags.** Into the `tags` taxonomy.
- **Links** between posts and categories or tags.
- **Attachments.** javaabu/mediapicker attachments of v1 posts (featured images, galleries, documents) are moved to the new posts, keeping their collection names.

v1's tables stay. Drop them once you've checked the content:

```php
foreach (\Javaabu\Cms\Upgrade\V1Tables::TABLES as $table) {
    Schema::dropIfExists(\Javaabu\Cms\Upgrade\V1Tables::renamed($table));
}
```

### 7. Install the rest

```bash
php artisan cms:install --no-content
```

This publishes the config if needed and syncs permissions. Then add the routes ([Routes](basic-usage/routes.md)) and give roles the new permissions ([Permissions](basic-usage/permissions.md)).

### 8. Menus, widgets and settings

- **Menus.** v1 used javaabu/menu-builder, whose menus aren't imported. Rebuild them at `admin/menus`; posts and post type archives are menu sources.
- **Widgets.** New in v2: add them at `admin/widgets`.
- **Site name and front page.** Set them at `admin/settings/site`.

### 9. Templates

v1's public views (`web.post-type.{type}.index|show`) become post-types' templates: `resources/views/post-types/{type}/archive.blade.php`, `single.blade.php` and `term.blade.php`, extending `cms::layouts.public` (or your layout). See [The public theme](basic-usage/public-theme.md).

### 10. URLs

v1's public URLs came from its post type slugs and root slugs. v2's are set per post type: pages at `/{slug}`, news at `/news/{year}/{month}/{slug}`. Match v1's with permalinks in config:

```php
// config/post-types.php
'permalinks' => [
    'news' => ['base' => 'news', 'structure' => '{slug}'],
],
```

post-types redirects other paths to a post (an old date, a changed category) to its permalink with a 301.
