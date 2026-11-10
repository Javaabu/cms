---
title: Permissions
sidebar_position: 7
---

Permissions are registered with [javaabu/permissions](https://github.com/Javaabu/permissions). Run `php artisan permissions:sync` after installing and after adding post types.

| Permission | Lets a user | From |
| --- | --- | --- |
| `view_dashboard` | See the dashboard | this package |
| `view_{type}`, `edit_{type}`, `delete_{type}`, `force_delete_{type}`, `publish_{type}` | List, write, trash, delete for good and publish posts of a type: `view_news`, `edit_pages`, … | javaabu/post-types |
| `view_{taxonomy}`, `edit_{taxonomy}`, `delete_{taxonomy}` | Manage `categories` and `tags` | javaabu/post-types |
| `view_media`, `edit_media`, `delete_media` (and `_others_`) | The media library | javaabu/mediapicker |
| `view_menus`, `edit_menus`, `delete_menus` | The menu builder | javaabu/dynamic-menubuilder |
| `view_widgets`, `edit_widgets` | Widgets | javaabu/widgets |
| `view_settings`, `edit_settings`, `edit_site_settings` | Settings, and the Site page | javaabu/settings |
| `view_roles`, `edit_roles`, `delete_roles` | Roles | javaabu/permissions |

The dashboard checks the `view` ability on `Javaabu\Cms\Dashboard\Dashboard`, with `Javaabu\Cms\Policies\DashboardPolicy` (registered with `Permissions::policy()`). Replace it with your own:

```php
// config/cms.php
'policies' => ['dashboard' => App\Policies\DashboardPolicy::class],

// or config/permissions.php
'policies' => [Javaabu\Cms\Dashboard\Dashboard::class => App\Policies\DashboardPolicy::class],

// or a service provider
Gate::policy(Javaabu\Cms\Dashboard\Dashboard::class, App\Policies\DashboardPolicy::class);
```

```php
namespace App\Policies;

use Illuminate\Database\Eloquent\Model;

class DashboardPolicy
{
    public function view(Model $user): bool
    {
        return $user->can('view_dashboard') || $user->can('view_news');
    }
}
```

Set `'register_permissions' => false` to register `view_dashboard` yourself.

## Guards and user models

Nothing assumes the `web` guard or `App\Models\User`. Each `Route::cmsAdmin()` call has its guard; the dashboard, the navigation and the packages' pages use that guard's user and its permissions. Posts' authors are a morph, so any user model can write them.

```php
// config/permissions.php
'guards' => ['web_admin', 'web'],
```

```php
Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
Route::cmsAdmin(['guard' => 'web', 'prefix' => 'portal', 'name' => 'portal.', 'roles' => false]);
```
