---
title: Installation & Setup
sidebar_position: 1.2
---

## 1. Install

```bash
composer require javaabu/cms
php artisan cms:install
```

`cms:install` does, in order (safe to run again):

1. publishes `config/cms.php` (`--force` overwrites it);
2. runs the migrations of the CMS and the packages it uses (`--no-migrate` skips them);
3. runs `php artisan permissions:sync`;
4. creates starter content, skipping anything already there (`--no-content` skips it):
   - a **General** category,
   - an **About us** page at `/about`,
   - a **Welcome to your new site** news article,
   - a **Main menu** in the header (Home, News, About us) and a **Footer menu** (About us),
   - a **Latest news** and a **Categories** widget in the sidebar.

   With javaabu/translatable enabled, the content is in English and Dhivehi. Otherwise it's in the app's language (Dhivehi when `app.locale` is `dv`).

## 2. Your user models

Admins need roles (javaabu/permissions) and their own media library (javaabu/mediapicker). Use any model and any guard:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Mediapicker\Concerns\OwnsMedia;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Permissions\Traits\HasRoles;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Admin extends Authenticatable implements MediaOwner
{
    use HasRoles;
    use InteractsWithMedia;
    use OwnsMedia;

    protected string $guard_name = 'web_admin';

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerMediapickerConversions($media);
    }

    public function registerMediaCollections(): void
    {
        $this->registerMediapickerCollections();
    }
}
```

## 3. Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);

    // Your own routes

    Route::cms();
});
```

`Route::cms()` registers pages at `/{slug}`, which matches any one-segment URL, so call it **after** your own routes. [Routes](basic-usage/routes.md) lists every route and option.

## 4. Permissions

```php
// config/permissions.php
'guards' => ['web_admin'],

'roles' => [
    'super_admin' => ['description' => 'Super Admin', 'permissions' => '*'],
    'editor' => ['description' => 'Editor', 'permissions' => ['view_dashboard', 'view_*', 'edit_news', 'edit_pages', 'publish_*']],
],
```

```bash
php artisan permissions:sync
```

[Permissions](basic-usage/permissions.md) lists them all.

## 5. Styles

Every page that needs the CMS's styles requires them through [javaabu/manifest](https://github.com/Javaabu/manifest), which injects them for you. With Vite, add manifest's plugin:

```js
// vite.config.js
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/css/app.css", "resources/js/app.js"] }), manifest()],
})
```

Without Vite, the prebuilt styles in the package's `dist/` are served instead. Blade pages need nothing from npm for the CMS. On Inertia pages, the React layouts import the stylesheet themselves; see [Inertia React](advanced-usage/inertia-react.md#styles) for the other packages' content styles.

The admin pages of post-types, widgets, menus, settings, media and roles use Tailwind utilities. Point Tailwind at their views:

```css
/* resources/css/app.css */
@import "tailwindcss";
@source "../../vendor/javaabu/*/resources/views";
```

## 6. Inertia React (optional)

```bash
composer require inertiajs/inertia-laravel
```

```dotenv
CMS_VIEWS=inertia
```

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { withCmsLayouts } from "@javaabu/cms"
import { resolvePage } from "@javaabu/manifest/inertia"
import { createRoot } from "react-dom/client"

createInertiaApp({
  resolve: withCmsLayouts((name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx"))),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

`withCmsLayouts()` gives pages without a layout of their own the CMS's: the admin layout on `Route::cmsAdmin()` routes and the theme on `Route::cms()` routes. Then run `php artisan manifest:check` and install what it lists. See [Inertia React](advanced-usage/inertia-react.md).

## Publishing

```bash
php artisan vendor:publish --tag=cms-config        # config/cms.php
php artisan vendor:publish --tag=cms-views         # resources/views/vendor/cms
php artisan vendor:publish --tag=cms-translations  # lang/vendor/cms
php artisan manifest:override cms js/components/ThemeLayout.tsx   # one React file
```
