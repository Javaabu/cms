---
title: Routes
sidebar_position: 1
---

Nothing is routed until you call the two macros, so you choose the prefix, the route names, the middleware and the guard.

## Route::cmsAdmin()

```php
Route::middleware('web')->group(function () {
    Route::cmsAdmin([
        'guard' => 'web_admin',          // the users who sign in to the admin; default: the default guard
        'prefix' => 'admin',             // default: admin
        'name' => 'admin.',              // route name prefix; default: admin.
        'middleware' => ['auth:web_admin', 'verified'],   // default: ['auth:{guard}']
        'controllers' => ['dashboard' => DashboardController::class],
    ]);
});
```

It registers each package's admin routes under the same prefix, name and guard:

| Option | URL | Route names | From |
| --- | --- | --- | --- |
| `dashboard` | `admin` | `admin.dashboard` | this package |
| `post_types` | `admin/news`, `admin/page`, `admin/taxonomies/categories` | `admin.news.*`, `admin.page.*`, `admin.taxonomies.categories.*` | `Route::postTypesAdmin()` |
| `media` | `admin/media` | `admin.media.*` | `Route::mediapicker()` |
| `menus` | `admin/menus` | `admin.menus.*` | `Route::menus()` |
| `widgets` | `admin/widgets` | `admin.widgets.*` | `Route::widgets()` |
| `settings` | `admin/settings` | `admin.settings.*` | `Route::settings()` |
| `roles` | `admin/roles` | `admin.roles.*` | `Route::roles()` |

Each option is `true` (the default), `false` to leave those routes out, or an array of options passed on to that package's macro:

```php
Route::cmsAdmin([
    'guard' => 'web_admin',
    'roles' => false,                                  // the app registers roles itself
    'menus' => ['prefix' => 'admin/navigation'],       // admin/navigation instead of admin/menus
    'post_types' => ['except' => ['page']],            // Route::postTypesAdmin()'s own options
]);
```

Call it once per user type. Each call keeps its own name prefix, so the navigation links to the right routes:

```php
Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
Route::cmsAdmin(['guard' => 'web', 'prefix' => 'portal', 'name' => 'portal.', 'roles' => false]);
```

## Route::cms()

```php
Route::middleware('web')->group(function () {
    // your own routes first

    Route::cms([
        'prefix' => '',          // default: none
        'name' => '',            // route name prefix; default: none
        'middleware' => [],      // added to the web group's
        'guard' => 'web_admin',  // who can preview drafts; default: the default guard
        'home' => true,          // the home page
        'post_types' => true,    // or Route::postTypes()'s options: ['only' => [...], 'except' => [...]]
    ]);
});
```

| URL | Route name | Page |
| --- | --- | --- |
| `/` | `home` | The home page: the front page chosen in the site settings, then the latest news |
| `/news` | `news.index` | The news archive |
| `/news/categories/{term}`, `/news/tags/{term}` | `news.term` | A category's or tag's news |
| `/news/{year}/{month}/{slug}` | `news.show` | A news article |
| `/{slug}` | `page.show` | A page |

Post types whose URLs start at the site root (pages) are registered last, after every other post type, so `/news` still reaches the archive. Your own routes must come before `Route::cms()` for the same reason.

With javaabu/translatable installed, the public routes also get its `language` middleware, so `?lang=dv` switches language.

Every route, admin or public, answers with JSON when the request expects it.
