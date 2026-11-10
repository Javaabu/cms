<?php

namespace Javaabu\Cms;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Cms\Assets\Assets;
use Javaabu\Cms\Console\ImportV1Command;
use Javaabu\Cms\Console\InstallCommand;
use Javaabu\Cms\Content\Defaults;
use Javaabu\Cms\Dashboard\Dashboard;
use Javaabu\Cms\Policies\DashboardPolicy;
use Javaabu\Cms\Routing\CmsRoutes;
use Javaabu\Cms\Settings\SiteSettings;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Widgets\Facades\Widgets;

class CmsServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    /**
     * The packages whose views and layouts follow config('cms.views') and
     * config('cms.layouts') when cms.sync_packages is on
     *
     * @var array<string, array<string, string>>
     */
    public const array SYNCED = [
        'post-types' => ['views' => 'views', 'layout' => 'admin', 'public_layout' => 'public', 'layout_section' => 'section'],
        'widgets' => ['views' => 'views', 'layout' => 'admin', 'layout_section' => 'section'],
        'dynamic-menubuilder' => ['views' => 'views', 'layout' => 'admin', 'layout_section' => 'section'],
        'settings' => ['views' => 'views', 'layout' => 'admin', 'layout_section' => 'section'],
        'mediapicker' => ['views' => 'views', 'layout' => 'admin', 'layout_section' => 'section'],
        'permissions' => ['views' => 'views', 'layout' => 'admin', 'layout_section' => 'section'],
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cms.php', 'cms');

        $this->app->singleton(CmsManager::class);
        $this->app->alias(CmsManager::class, 'cms');

        $this->syncPackages();
    }

    public function boot(): void
    {
        // Again, for config set after the providers registered (tests, runtime)
        $this->syncPackages();

        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cms');
        Blade::componentNamespace('Javaabu\\Cms\\Views\\Components', 'cms');

        $this->registerFrontendAssets();
        $this->registerContent();
        $this->registerSettings();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                InstallCommand::class,
                ImportV1Command::class,
            ]);

            $this->registerPublishing();
        }
    }

    /**
     * One stack and one set of layouts for every package the CMS is made of
     */
    protected function syncPackages(): void
    {
        $config = $this->app['config'];

        if (! $config->get('cms.sync_packages', true)) {
            return;
        }

        $values = [
            'views' => $config->get('cms.views', 'blade'),
            'admin' => $config->get('cms.layouts.admin', 'cms::layouts.admin'),
            'public' => $config->get('cms.layouts.public', 'cms::layouts.public'),
            'section' => $config->get('cms.layouts.section', 'content'),
        ];

        foreach (self::SYNCED as $package => $keys) {
            foreach ($keys as $key => $value) {
                $config->set("{$package}.{$key}", $values[$value]);
            }
        }
    }

    /**
     * The Inertia pages (cms::Admin/Dashboard, cms::Public/Home), the
     * components apps import as @javaabu/cms, and the theme's styles
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets(Assets::PACKAGE, __DIR__ . '/../resources', function (Package $package) {
            Assets::register($package);
        });
    }

    /**
     * The default content model: taxonomies, post types, menu and widget
     * locations, and the CMS's widget types
     */
    protected function registerContent(): void
    {
        foreach ((array) config('cms.taxonomies', []) as $taxonomy) {
            $taxonomy = is_string($taxonomy) ? (Defaults::taxonomy($taxonomy) ?? $taxonomy) : $taxonomy;

            PostTypes::taxonomy($taxonomy);
        }

        foreach ((array) config('cms.post_types', []) as $type) {
            $type = is_string($type) ? (Defaults::postType($type) ?? $type) : $type;

            PostTypes::register($type);
        }

        foreach ((array) config('cms.menu_locations', []) as $key => $depth) {
            if (is_int($key)) {
                [$key, $depth] = [$depth, null];
            }

            Menus::location((string) $key, static::locationLabel((string) $key))->maxDepth($depth);
        }

        foreach ((array) config('cms.widget_locations', []) as $location) {
            Widgets::location((string) $location, fn () => __(static::locationLabel((string) $location)));
        }

        $widgets = (array) config('cms.widgets', []);

        if ($widgets !== []) {
            Widgets::register($widgets);
        }
    }

    protected function registerSettings(): void
    {
        if (config('cms.settings', true)) {
            Settings::page(SiteSettings::class);
        }
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('cmsAdmin', function (array $options = []) {
            CmsRoutes::registerAdmin($this, $options);
        });

        Route::macro('cms', function (array $options = []) {
            CmsRoutes::registerPublic($this, $options);
        });
    }

    /**
     * view_dashboard and the dashboard policy, which the app can override
     */
    protected function registerPermissions(): void
    {
        Permissions::policy(Dashboard::class, config('cms.policies.dashboard', DashboardPolicy::class));

        if (! config('cms.register_permissions', true)) {
            return;
        }

        Permissions::register('dashboard', [
            'view_dashboard' => 'View the dashboard',
        ], label: 'Dashboard');
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/cms.php' => config_path('cms.php'),
        ], 'cms-config');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/cms'),
        ], 'cms-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/cms'),
        ], 'cms-views');
    }

    /**
     * The English label of a default location (translated when shown)
     */
    public static function locationLabel(string $key): string
    {
        return match ($key) {
            'header' => 'Header',
            'footer' => 'Footer',
            'sidebar' => 'Sidebar',
            default => ucfirst(str_replace(['-', '_'], ' ', $key)),
        };
    }
}
