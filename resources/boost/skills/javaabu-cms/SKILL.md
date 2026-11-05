---
name: javaabu-cms
description: Set up and extend a Laravel site with javaabu/cms v2. Use when installing the CMS (cms:install), registering its routes (Route::cmsAdmin, Route::cms), changing the default content model (pages, news, categories, tags, menu and widget locations, Content\Defaults), adding admin navigation links (Cms::navigation, NavigationItem), customising the dashboard or home page, the public theme or admin layout (Blade cms::layouts.*, React ThemeLayout, AdminLayout, withCmsLayouts), the Site settings (Cms::siteName, cms_front_page), the recent-posts and terms widgets, permissions (view_dashboard, DashboardPolicy), translations, or upgrading a v1 site (cms:import-v1).
license: MIT
metadata:
  author: Javaabu
---

# A CMS with javaabu/cms

The docs ship with the package: `vendor/javaabu/cms/llms.txt` lists every page in `vendor/javaabu/cms/docs/`. The CMS is made of other packages; their skills cover them in depth: `javaabu-post-types`, `javaabu-widgets`, `javaabu-dynamic-menubuilder`, `javaabu-settings`, `javaabu-permissions`.

| Task | Read |
| --- | --- |
| Install, routes, content model, navigation, theme | This file |
| Every option, route, method, prop and export | [references/api.md](references/api.md) |
| Upgrading a v1 site | [references/upgrading.md](references/upgrading.md) |
| Mistakes to avoid | [references/common-mistakes.md](references/common-mistakes.md) |

## 1. Install

```bash
composer require javaabu/cms
php artisan cms:install            # config, migrations, permissions:sync, starter content
```

## 2. Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);

    // the app's own routes

    Route::cms(); // last: pages are /{slug}
});
```

`admin` (dashboard), `admin/news`, `admin/page`, `admin/taxonomies/categories`, `admin/media`, `admin/menus`, `admin/widgets`, `admin/settings`, `admin/roles`; `/`, `/news`, `/news/categories/{term}`, `/news/{year}/{month}/{slug}`, `/{slug}`. Turn a section off with `'roles' => false`, or pass it options: `'menus' => ['prefix' => 'admin/navigation']`.

## 3. Permissions

```php
// config/permissions.php
'roles' => [
    'editor' => ['description' => 'Editor', 'permissions' => ['view_dashboard', 'view_news', 'edit_news', 'publish_news', 'view_pages', 'edit_pages']],
],
```

```bash
php artisan permissions:sync
```

## 4. Content model

```php
// config/cms.php
use Javaabu\Cms\Content\Defaults;

'post_types' => ['page', Defaults::news()->permalink('{slug}')],
'taxonomies' => ['categories', 'tags'],
'menu_locations' => ['header' => 2, 'footer' => 1],
'widget_locations' => ['sidebar', 'footer'],
```

More post types: register them with javaabu/post-types in a service provider. They appear in the navigation, dashboard, routes, widgets and menus by themselves.

```php
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;

PostTypes::register(
    PostType::make('events')->labels('Event', 'Events')->icon('calendar')->supports(['content', 'excerpt', 'media', 'scheduling'])
);
```

## 5. Navigation

```php
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Navigation\NavigationItem;

public function boot(): void
{
    Cms::navigation()->add(
        NavigationItem::make('reports')
            ->label(fn () => __('Reports'))
            ->route('reports.index')     // admin.reports.index
            ->icon('chart-column')
            ->group('content')
            ->can('view_reports'),
    );
}
```

## 6. Theme

```blade
@extends('cms::layouts.public')

@section('content')
    <h1 class="cms-heading">{{ __('Contact us') }}</h1>
@endsection
```

```tsx
// resources/js/app.tsx
import { withCmsLayouts } from "@javaabu/cms"
import { resolvePage } from "@javaabu/manifest/inertia"

createInertiaApp({ resolve: withCmsLayouts((name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx"))) })
```

Site details: `Cms::siteName()`, `Cms::tagline()`, `Cms::footerText()`, `Cms::frontPage()`, `Cms::site()`.

## 7. Change behaviour

Extend `DashboardController::data()` or `HomeController::data()` and set `config('cms.controllers')`. Replace the dashboard policy with `config('cms.policies.dashboard')`, `config('permissions.policies')` or `Gate::policy(Dashboard::class, …)`. Publish views with `--tag=cms-views`; override a React file with `php artisan manifest:override cms js/components/ThemeLayout.tsx`.
