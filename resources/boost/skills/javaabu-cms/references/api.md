# javaabu/cms API

## Route::cmsAdmin(array $options)

| Option | Default | |
| --- | --- | --- |
| `guard` | default guard | Users of the admin |
| `prefix` | `admin` | |
| `name` | `admin.` | Route name prefix |
| `middleware` | `['auth:{guard}']` | |
| `controllers.dashboard` | `config('cms.controllers.dashboard')` | |
| `dashboard` | `true` | `GET {prefix}` → `{name}dashboard` |
| `post_types`, `media`, `menus`, `widgets`, `settings`, `roles` | `true` | `false`, or options for `postTypesAdmin()`, `mediapicker()`, `menus()`, `widgets()`, `settings()`, `roles()` |

## Route::cms(array $options)

| Option | Default | |
| --- | --- | --- |
| `prefix`, `name` | none | |
| `middleware` | `[]` | Plus `language` with javaabu/translatable |
| `guard` | default | Who can preview drafts |
| `controllers.home` | `config('cms.controllers.home')` | |
| `home` | `true` | `GET /` → `{name}home` |
| `post_types` | `true` | `false`, or `postTypes()` options (`only`, `except`); root post types go last |

## config/cms.php

`views` (`blade`/`inertia`), `layouts.admin`, `layouts.public`, `layouts.section`, `sync_packages`, `taxonomies`, `post_types`, `menu_locations`, `widget_locations`, `widgets`, `settings`, `home.post_type`, `home.count`, `dashboard.recent`, `controllers.dashboard`, `controllers.home`, `policies.dashboard`, `register_permissions`, `translatable`, `upgrade.rename_v1_tables`, `upgrade.prefix`.

## Cms facade

`navigation()`, `navigationFor(?Model $user, ?Request $request)`, `siteName()`, `tagline()`, `footerText()`, `frontPage()`, `homePostType()`, `site()` (`name`, `tagline`, `footer_text`, `home_url`, `locale`, `direction`, `locales`), `strings()`, `menus()`, `widgets()`, `views()`, `layout('admin'|'public')`, `layoutSection()`.

## Navigation

- `Navigation`: `group($key, $label, $order)`, `add(NavigationItem ...$items)`, `provide(fn () => [...])`, `remove(...$keys)`, `get($key)`, `has($key)`, `all()`, `resolve($user, $prefix, $current)`.
- `NavigationItem::make($key)`: `label()`, `route($name, $parameters = [], relative: true)`, `url()`, `icon()`, `group()`, `order()`, `can($ability, $arguments)` or `can(fn (?Model $user) => …)`, `active($patterns)`, `children([...])`.
- Default keys: `dashboard`, `post-type-{key}`, `taxonomy-{key}`, `media`, `menus`, `widgets`, `settings`, `roles`. Groups: `content`, `appearance`, `administration`.

## Content\Defaults

`page()`, `news()`, `categories()`, `tags()`, `postType($key)`, `taxonomy($key)`.

## Settings (Site page, key `site`, permission `edit_site_settings`)

`cms_site_name`, `cms_tagline`, `cms_front_page` (page id), `cms_home_post_type`, `cms_footer_text`.

## Widgets

- `recent-posts` (`RecentPostsWidget`): settings `title`, `post_type`, `count`, `show_date`; data `posts`, `archive_url`, `archive_label`.
- `terms` (`TermsWidget`): settings `title`, `source` (`news:categories`), `show_counts`, `hide_empty`; data `terms`, `show_counts`.

## Views and pages

| | Blade | Inertia / React |
| --- | --- | --- |
| Admin layout | `cms::layouts.admin` | `AdminLayout` |
| Theme | `cms::layouts.public` (`@section('full_width', true)` hides the sidebar) | `ThemeLayout` |
| Dashboard | `cms::admin.dashboard` | `cms::Admin/Dashboard` (`post_types`, `recent`, `strings`) |
| Home | `cms.home` or `cms::public.home` | `cms::Public/Home` (`site`, `page`, `post_type`, `posts`, `archive_url`, `strings`) |
| Navigation | `<x-cms::admin-navigation guard="…" />` | `AdminNavigation` |
| Language switcher | `<x-cms::language-switcher />` | `LanguageSwitcher` |

Shared props on CMS routes: `cms.area`, `cms.site`, `cms.strings`, `cms.navigation` (admin); `menus`, `widgets` (public).

`@javaabu/cms` exports: `AdminLayout`, `ThemeLayout`, `CmsLayout`, `AdminNavigation`, `LanguageSwitcher`, `withCmsLayouts`, `useCms`, `cmsWidgets`, `registerCmsWidgets`, `RecentPosts`, `Terms`, `Dashboard`, `Home`, `format`, and the types.

## Commands

- `cms:install [--no-migrate] [--no-content] [--force]`
- `cms:import-v1 [--type=v1:v2]... [--taxonomy=v1:v2]... [--dry-run] [--force]`

## Permissions

`view_dashboard` (this package), plus post-types' `view_/edit_/delete_/force_delete_/publish_{type}` and `view_/edit_/delete_{taxonomy}`, mediapicker's `view_media`…, menubuilder's `view_menus`…, widgets' `view_widgets`/`edit_widgets`, settings' `view_settings`/`edit_settings`/`edit_site_settings`, permissions' `view_roles`….
