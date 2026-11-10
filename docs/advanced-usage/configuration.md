---
title: Configuration
sidebar_position: 1
---

Publish the config with `php artisan vendor:publish --tag=cms-config`. Every option has a default; you don't need the file for the common case.

| Option | Default | What it does |
| --- | --- | --- |
| `views` | `env('CMS_VIEWS', 'blade')` | `blade` or `inertia`: the stack every page renders with |
| `layouts.admin` | `cms::layouts.admin` | The Blade layout of admin pages |
| `layouts.public` | `cms::layouts.public` | The Blade layout of public pages (the theme) |
| `layouts.section` | `content` | The section pages fill |
| `sync_packages` | `true` | Apply `views` and `layouts` to post-types, widgets, dynamic-menubuilder, settings, mediapicker and permissions. Turn off to configure each package yourself |
| `taxonomies` | `['categories', 'tags']` | Taxonomies to register: keys of `Content\Defaults`, or `Taxonomy` instances or class names |
| `post_types` | `['page', 'news']` | Post types to register: keys of `Content\Defaults`, or `PostType` instances or class names |
| `menu_locations` | `['header' => 2, 'footer' => 1]` | Menu locations and their maximum depth (or a list of keys) |
| `widget_locations` | `['sidebar', 'footer']` | Widget locations |
| `widgets` | `[RecentPostsWidget::class, TermsWidget::class]` | Widget types to register |
| `settings` | `true` | Register the Site settings page |
| `home.post_type` | `news` | The post type the home page lists, unless the site settings say otherwise |
| `home.count` | `6` | How many posts the home page lists |
| `dashboard.recent` | `5` | How many recently edited posts the dashboard lists |
| `controllers.dashboard` | `DashboardController::class` | The dashboard's controller |
| `controllers.home` | `HomeController::class` | The home page's controller |
| `policies.dashboard` | `DashboardPolicy::class` | The dashboard's policy |
| `register_permissions` | `true` | Register `view_dashboard` |
| `translatable` | `true` | Use javaabu/translatable when it's installed (the switcher, translatable site settings) |
| `upgrade.rename_v1_tables` | `true` | Rename v1's tables out of the way during `migrate` ([Upgrading from v1](../upgrading.md)) |
| `upgrade.prefix` | `cms_v1_` | What v1's tables are renamed to |

## With `sync_packages` on

| Package | Keys the CMS sets |
| --- | --- |
| post-types | `views`, `layout` (admin), `public_layout` (public), `layout_section` |
| widgets, dynamic-menubuilder, settings, mediapicker, permissions | `views`, `layout` (admin), `layout_section` |

## The facade

`Javaabu\Cms\Facades\Cms` (`CmsManager`):

| Method | Returns |
| --- | --- |
| `navigation()` | The `Navigation` registry |
| `navigationFor(?Model $user, ?Request $request = null)` | The navigation for a user, as arrays |
| `siteName()`, `tagline()`, `footerText()` | From the Site settings |
| `frontPage()` | The published page shown on the home page, or null |
| `homePostType()` | The post type the home page lists, or null |
| `site()` | `name`, `tagline`, `footer_text`, `home_url`, `locale`, `direction`, `locales` |
| `strings()` | The layouts' strings in the current language |
| `menus()` | The CMS's menu locations' items, for React |
| `widgets()` | The CMS's widget locations' widgets, for React |
| `views()` | `blade` or `inertia` |
| `layout('admin' or 'public')`, `layoutSection()` | The layouts in use |

`Javaabu\Cms\Routing\CmsRoutes::homeUrl()` is the home page's URL, and `CmsRoutes::adminName($request)` the admin route name prefix of a request.
