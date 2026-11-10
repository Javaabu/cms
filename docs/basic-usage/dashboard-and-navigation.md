---
title: Dashboard and navigation
sidebar_position: 3
---

## The dashboard

`GET /admin` (`admin.dashboard`) shows, for each post type the user can view:

- how many posts are published, scheduled, drafts and pending review;
- a link to the list and, when they can add posts, an "Add" link;

and the posts edited most recently (`cms.dashboard.recent`, default 5), linking to their edit page when the user can edit them.

It needs the `view_dashboard` permission (the `view` ability on `Javaabu\Cms\Dashboard\Dashboard`). Ask for the data as JSON with `Accept: application/json`.

Add to it by extending the controller:

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    protected function data(Request $request, ?Model $user): array
    {
        return [
            ...parent::data($request, $user),
            'subscribers' => Subscriber::query()->count(),
        ];
    }
}
```

```php
// config/cms.php
'controllers' => ['dashboard' => App\Http\Controllers\Admin\DashboardController::class],
```

Then show it in your copy of the view (`cms::admin.dashboard`) or page (`cms::Admin/Dashboard`).

## The navigation

`Cms::navigation()` holds the admin's links, in groups:

| Group | Links (keys) |
| --- | --- |
| (none) | `dashboard` |
| Content | `post-type-{key}` for every post type, `taxonomy-{key}` for every taxonomy, `media` |
| Appearance | `menus`, `widgets` |
| Administration | `settings`, `roles` |

A link only shows when its route is registered and the user may see it (`viewAny` on the post type, taxonomy, media, menu, widget location, settings page or role). Post types and taxonomies registered later are picked up, as they're read each time the navigation is built.

### Adding links

```php
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Navigation\NavigationItem;

public function boot(): void
{
    Cms::navigation()
        ->group('reports', fn () => __('Reports'), order: 25)
        ->add(
            NavigationItem::make('visits')
                ->label(fn () => __('Visits'))
                ->route('reports.visits')           // relative to the admin route names: admin.reports.visits
                ->icon('chart-column')              // a Lucide icon name
                ->group('reports')
                ->order(10)
                ->can('view_reports'),              // an ability or permission, with arguments, or a closure
            NavigationItem::make('help')
                ->label(fn () => __('Help'))
                ->children([
                    NavigationItem::make('docs')->label(fn () => __('Docs'))->url('https://docs.javaabu.com'),
                ]),
        )
        ->remove('roles');
}
```

| Method | What it does |
| --- | --- |
| `NavigationItem::make($key)` | A link. A key that's already there replaces that link |
| `label($string or closure)` | The text. Strings are translated when shown; defaults to the key as a headline |
| `route($name, $parameters = [], relative: true)` | A named route, after the admin name prefix unless `relative: false`. Hidden when the route doesn't exist |
| `url($url or closure)` | A URL instead of a route |
| `icon($name)` | A Lucide icon name, for your layout (`data-icon` in the default markup) |
| `group($key)` | The group it's in; unknown groups put it at the top |
| `order($int)` | Position within its group (default 100) |
| `can($ability, $arguments)` or `can(fn (?Model $user) => …)` | Who sees it |
| `active($patterns)` | Route name patterns that make it current; default: its route's resource (`news.*`) |
| `children([...])` | Links under it. A link with no URL shows when one of its children does |

`Navigation` also has `group($key, $label, $order)`, `provide(fn () => [...])` for links worked out each time, `remove(...$keys)`, `get($key)`, `has($key)` and `resolve($user, $prefix, $current_route)`.

### Rendering it

The admin layout does this for you. In your own layout:

```blade
<x-cms::admin-navigation />
<x-cms::admin-navigation guard="web_admin" />
```

```tsx
import { AdminNavigation, useCms } from "@javaabu/cms"

const cms = useCms()
<AdminNavigation groups={cms?.navigation ?? []} label={cms?.strings.navigation} />
```

In PHP, `Cms::navigationFor($user, $request)` gives the groups as arrays: `key`, `label` and `items` with `key`, `label`, `url`, `icon`, `active` and `children`.
