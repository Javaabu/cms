<?php

namespace Javaabu\Cms\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Http\Controllers\DashboardController;
use Javaabu\Cms\Http\Controllers\HomeController;
use Javaabu\Cms\Http\Middleware\ShareCmsData;
use Javaabu\Cms\Support\Translations;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;

/**
 * The CMS's routes, registered by two macros so apps choose the prefix,
 * middleware and guard:
 *
 * Route::cmsAdmin() registers, under one prefix, name and guard:
 *
 * | Option       | Routes                                         | Names                         |
 * | ------------ | ---------------------------------------------- | ----------------------------- |
 * | `dashboard`  | GET {prefix}                                   | {name}dashboard               |
 * | `post_types` | {prefix}/{type}, {prefix}/taxonomies/{tax}     | {name}{type}.*, {name}taxonomies.{tax}.* |
 * | `media`      | {prefix}/media (javaabu/mediapicker)           | {name}media.*                 |
 * | `menus`      | {prefix}/menus (javaabu/dynamic-menubuilder)   | {name}menus.*                 |
 * | `widgets`    | {prefix}/widgets (javaabu/widgets)             | {name}widgets.*               |
 * | `settings`   | {prefix}/settings (javaabu/settings)           | {name}settings.*              |
 * | `roles`      | {prefix}/roles (javaabu/permissions)           | {name}roles.*                 |
 *
 * Each option is true (the default), false to leave those routes out, or an
 * array of options for that package's own route macro.
 *
 * Route::cms() registers the public site: the home page ({name}home), then
 * every public post type's archive, term and single routes (javaabu/post-types),
 * with post types at the site root (pages: /{slug}) last. Call it after your
 * own routes, as /{slug} matches any single segment.
 */
class CmsRoutes
{
    /** The route action key holding the admin route name prefix */
    public const string NAME_ACTION = 'cms_name';

    /** The route action key holding the guard */
    public const string GUARD_ACTION = 'cms_guard';

    /** The admin sections Route::cmsAdmin() registers, in order */
    public const array SECTIONS = ['dashboard', 'post_types', 'media', 'menus', 'widgets', 'settings', 'roles'];

    /**
     * The admin route name prefix of each guard ('*' without one)
     *
     * @var array<string, string>
     */
    protected static array $admin_names = [];

    protected static ?string $home_route = null;

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controllers?: array{dashboard?: class-string},
     *     dashboard?: bool,
     *     post_types?: bool|array<string, mixed>,
     *     media?: bool|array<string, mixed>,
     *     menus?: bool|array<string, mixed>,
     *     widgets?: bool|array<string, mixed>,
     *     settings?: bool|array<string, mixed>,
     *     roles?: bool|array<string, mixed>,
     * }  $options
     */
    public static function registerAdmin(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'admin', '/');
        $name = $options['name'] ?? 'admin.';
        $middleware = [
            ...(array) ($options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth']),
            ShareCmsData::class . ':admin',
        ];

        if (! isset(static::$admin_names[$guard ?? '*'])) {
            static::$admin_names[$guard ?? '*'] = $name;
        }

        $common = ['guard' => $guard, 'middleware' => $middleware];
        $path = fn (string $segment) => trim($prefix . '/' . $segment, '/');

        if ($options['dashboard'] ?? true) {
            $controller = $options['controllers']['dashboard'] ?? config('cms.controllers.dashboard', DashboardController::class);

            $router->group([
                'prefix' => $prefix,
                'middleware' => $middleware,
                self::NAME_ACTION => $name,
                self::GUARD_ACTION => $guard,
            ], function (Router $router) use ($controller, $name) {
                $router->get('/', [$controller, 'index'])->name("{$name}dashboard");
            });
        }

        if (($section = static::section($options, 'post_types')) !== false) {
            $router->postTypesAdmin([...$common, 'prefix' => $prefix, 'name' => $name, ...$section]);
        }

        if (($section = static::section($options, 'media')) !== false && Route::hasMacro('mediapicker')) {
            $router->mediapicker([...$common, 'prefix' => $path('media'), 'name' => "{$name}media", ...$section]);
        }

        if (($section = static::section($options, 'menus')) !== false) {
            $router->menus([...$common, 'prefix' => $path('menus'), 'name' => "{$name}menus", ...$section]);
        }

        if (($section = static::section($options, 'widgets')) !== false) {
            $router->widgets([...$common, 'prefix' => $path('widgets'), 'name' => "{$name}widgets", ...$section]);
        }

        if (($section = static::section($options, 'settings')) !== false) {
            $router->settings([...$common, 'prefix' => $path('settings'), 'name' => "{$name}settings", ...$section]);
        }

        if (($section = static::section($options, 'roles')) !== false) {
            $router->roles([...$common, 'guard' => $guard ?? config('auth.defaults.guard'), 'prefix' => $path('roles'), 'name' => "{$name}roles", ...$section]);
        }
    }

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controllers?: array{home?: class-string},
     *     home?: bool,
     *     post_types?: bool|array<string, mixed>,
     * }  $options
     */
    public static function registerPublic(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? '', '/');
        $name = $options['name'] ?? '';
        $middleware = [...(array) ($options['middleware'] ?? []), ShareCmsData::class . ':public'];

        if (Translations::installed() && array_key_exists('language', $router->getMiddleware())) {
            $middleware[] = 'language';
        }

        if ($options['home'] ?? true) {
            $controller = $options['controllers']['home'] ?? config('cms.controllers.home', HomeController::class);

            $router->group([
                'prefix' => $prefix,
                'middleware' => $middleware,
                self::NAME_ACTION => $name,
                self::GUARD_ACTION => $guard,
            ], function (Router $router) use ($controller, $name) {
                $router->get('/', [$controller, 'index'])->name("{$name}home");
            });

            static::$home_route ??= "{$name}home";
        }

        $section = static::section($options, 'post_types');

        if ($section === false) {
            return;
        }

        $common = ['guard' => $guard, 'prefix' => $prefix, 'name' => $name, 'middleware' => $middleware, ...$section];

        // Post types at the site root (/{slug}) catch every single segment, so they go last
        $root = array_keys(array_filter(PostTypes::types(), fn (PostType $type) => $type->isPublic() && $type->getSlug() === ''));
        $only = $common['only'] ?? null;
        $except = (array) ($common['except'] ?? []);
        $selected = array_keys(PostTypes::types());

        if (! empty($only)) {
            $selected = array_intersect($selected, (array) $only);
        }

        $selected = array_diff($selected, $except);
        $others = array_values(array_diff($selected, $root));
        $last = array_values(array_intersect($selected, $root));

        if ($others !== []) {
            $router->postTypes([...$common, 'only' => $others, 'except' => []]);
        }

        if ($last !== []) {
            $router->postTypes([...$common, 'only' => $last, 'except' => []]);
        }
    }

    /**
     * The admin route name prefix for a request: from its route, or the first
     * Route::cmsAdmin() call for its guard (or any guard)
     */
    public static function adminName(?Request $request = null): string
    {
        $action = $request?->route()?->getAction(self::NAME_ACTION);

        if (is_string($action)) {
            return $action;
        }

        $guard = $request?->route()?->getAction(self::GUARD_ACTION);

        return static::$admin_names[$guard ?? '*'] ?? static::$admin_names['*'] ?? (reset(static::$admin_names) ?: 'admin.');
    }

    /**
     * The guard of a request's CMS route
     */
    public static function guard(?Request $request = null): ?string
    {
        $guard = $request?->route()?->getAction(self::GUARD_ACTION);

        return is_string($guard) ? $guard : null;
    }

    public static function homeUrl(): ?string
    {
        return static::$home_route && Route::has(static::$home_route) ? route(static::$home_route) : null;
    }

    public static function flush(): void
    {
        static::$admin_names = [];
        static::$home_route = null;
    }

    /**
     * An admin or public section's options: false when it's off
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|false
     */
    protected static function section(array $options, string $key): array|false
    {
        $value = $options[$key] ?? true;

        if ($value === false) {
            return false;
        }

        return is_array($value) ? $value : [];
    }
}
