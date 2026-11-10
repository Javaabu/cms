---
title: Controllers, policies and views
sidebar_position: 2
---

## Controllers

| Controller | Config | Route option | Override |
| --- | --- | --- | --- |
| `Javaabu\Cms\Http\Controllers\DashboardController` | `cms.controllers.dashboard` | `Route::cmsAdmin(['controllers' => ['dashboard' => …]])` | `data(Request $request, ?Model $user): array`, `strings()` |
| `Javaabu\Cms\Http\Controllers\HomeController` | `cms.controllers.home` | `Route::cms(['controllers' => ['home' => …]])` | `data(Request $request): array`, `page()`, `posts()`, `strings()` |

The other admin pages are the packages' own: override them the way each package documents, through `config('post-types.controllers')`, `config('widgets.controllers')` and so on, or by passing their options through `Route::cmsAdmin()`.

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\HomeController as BaseHomeController;
use Javaabu\PostTypes\Facades\PostTypes;

class HomeController extends BaseHomeController
{
    protected function data(Request $request): array
    {
        return [
            ...parent::data($request),
            'events' => PostTypes::get('events')->query()->published()->latestPublished()->take(3)->get(['id', 'title']),
        ];
    }
}
```

## Policies

See [Permissions](../basic-usage/permissions.md).

## Views

| View | Blade | Inertia |
| --- | --- | --- |
| Admin layout | `cms::layouts.admin` | `AdminLayout` |
| Theme | `cms::layouts.public` | `ThemeLayout` |
| Dashboard | `cms::admin.dashboard` | `cms::Admin/Dashboard` |
| Home page | `cms.home`, else `cms::public.home` | `cms::Public/Home` |
| Navigation | `<x-cms::admin-navigation />` | `AdminNavigation` |
| Language switcher | `<x-cms::language-switcher />` | `LanguageSwitcher` |
| Recent posts widget | `cms::widgets.recent-posts` | `RecentPosts` |
| Terms widget | `cms::widgets.terms` | `Terms` |

Blade: `php artisan vendor:publish --tag=cms-views` copies them to `resources/views/vendor/cms`, where yours win. Or point `cms.layouts.*` at your own layouts.

React: `php artisan manifest:override cms js/Pages/Admin/Dashboard.tsx` copies one file to `resources/vendor/cms/js/…`, where yours wins, for pages and components alike.

Use logical CSS (`margin-inline-start`, `ms-4`, `text-start`) in your copies so they work right to left.
