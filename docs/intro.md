---
title: Introduction
sidebar_position: 1.0
---

# CMS

[javaabu/cms](https://github.com/Javaabu/cms) is a complete CMS for Laravel. It doesn't reimplement content, menus or widgets: it assembles finished Javaabu packages, sets them up the same way, and adds what ties them into a site.

| You get | From |
| --- | --- |
| Pages at the site root (`/about`), and news with categories and tags | [javaabu/post-types](https://github.com/Javaabu/post-types), with [javaabu/block-editor](https://github.com/Javaabu/block-editor) content, [javaabu/mediapicker](https://github.com/Javaabu/mediapicker) media and [javaabu/meta](https://github.com/Javaabu/meta) fields |
| Header and footer menus | [javaabu/dynamic-menubuilder](https://github.com/Javaabu/dynamic-menubuilder) |
| Sidebar and footer widgets, plus recent-posts and categories widgets | [javaabu/widgets](https://github.com/Javaabu/widgets) |
| A "Site" settings page: name, tagline, front page, footer text | [javaabu/settings](https://github.com/Javaabu/settings) |
| Roles and permissions for any guard | [javaabu/permissions](https://github.com/Javaabu/permissions) |
| An admin dashboard and navigation that follows each user's permissions | this package |
| A public theme, in Blade and Inertia React | this package |
| `php artisan cms:install` with starter content in English and Dhivehi | this package |
| `php artisan cms:import-v1` for sites on v1 | this package |

Everything renders with Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or Inertia React (@javaabu/forms and Onu), chosen with one config option. With [javaabu/translatable](https://github.com/Javaabu/translatable) installed, content, menus, widgets and settings are translatable, and the theme gets a language switcher.

```bash
composer require javaabu/cms
php artisan cms:install
```

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);

    // Your own routes here

    Route::cms(); // last: pages live at /{slug}
});
```

Then give your admin role `view_dashboard` and the content permissions, and open `/admin`.

Next: [Requirements](requirements.md), then [Installation & Setup](installation-and-setup.md). Upgrading a v1 site? Read [Upgrading from v1](upgrading.md).
