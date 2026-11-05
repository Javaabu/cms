# Upgrading a javaabu/cms v1 site

1. Back up the database and storage.
2. Require `javaabu/cms:^2.0` (PHP 8.4, Laravel 12+); remove `javaabu/menu-builder` and `javaabu/helpers` if only the CMS used them.
3. Delete v1's published views, JS blocks and Sass, routes using v1's controllers or `Javaabu\Cms\Support\Routes`, and references to `Javaabu\Cms\Models\*` and `Javaabu\Cms\Enums\*`. Move `config/cms.php` aside; v2's keys differ.
4. `php artisan cms:import-v1 --dry-run` lists v1's post types and where they'll go. Register the ones v2 doesn't have with `PostTypes::register()`, with meta fields named like v1's columns (`document_no`, `expire_at`, `ref_no`, `video_url`, `format`, `page_style`, `coords`, `city_id`, `recently_updated`).
5. `php artisan migrate`: v1's tables are renamed to `cms_v1_*` before post-types creates `posts`.
6. `php artisan cms:import-v1` (`--type=blog:news`, `--taxonomy=news-categories:categories`). Posts keep their ids when `posts` is empty; Editor.js content becomes block-editor documents; categories keep nesting; tags, translations, meta and mediapicker attachments come along.
7. `php artisan cms:install --no-content`, add `Route::cmsAdmin()` and `Route::cms()`, give roles the new permissions.
8. Rebuild menus at `admin/menus` (javaabu/menu-builder menus aren't imported); add widgets; set the Site settings.
9. Move v1's public templates to `resources/views/post-types/{type}/{archive|single|term}.blade.php`; set `config('post-types.permalinks')` to match v1's URLs.
10. Drop the `cms_v1_*` tables once the content is checked.
