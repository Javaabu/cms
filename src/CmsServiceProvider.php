<?php

namespace Javaabu\Cms;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    protected array $migrations = [
        'create_category_types_table',
        'create_post_types_table',
        'create_posts_table',
        'create_categories_table',
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->offerPublishing();

        $this->registerPolicies();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms');

        $this->registerRouteModelBindings();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'cms');

        $this->registerSingletons();
    }

    /**
     * Offer publishing
     *
     * @return void
     */
    public function offerPublishing(): void
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('cms.php'),
            ], 'cms-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/cms'),
            ], 'cms-views');

            // Publish flags
            $this->publishes([
                __DIR__ . '/../resources/dist/flags' => public_path('vendors/flags'),
            ], 'cms-flags');

            // Publish migrations
            foreach ($this->migrations as $migration) {
                $vendorMigration = __DIR__ . '/../database/migrations/' . $migration . '.php';
                $appMigration = $this->generateMigrationName($migration, now()->addSecond());

                $this->publishes([
                    $vendorMigration => $appMigration,
                ], 'cms-migrations');
            }
        }
    }

    public function registerPolicies()
    {
        $policies = $this->getPolicies();

        foreach ($policies as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    public function registerSingletons(): void
    {
        $this->app->singleton(Cms::class, function () {
            return new Cms();
        });

        $this->app->alias(Cms::class, 'cms');
    }

    public function registerRouteModelBindings()
    {
        Route::bind('post_type', function ($value, $route) {
            try {
                return PostType::whereSlug($value)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('post', function ($value, $route) {
            $post_type = $route->parameter('post_type');
            $post_type_slug = is_object($post_type) ? $post_type->slug : $post_type;

            try {
                return Post::where('type', $post_type_slug ?: -1)
                    ->findOrFail($value);
            } catch (ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('post_slug', function ($value, $route) {
            $language = $route->parameter('language');
            $post_type = $route->parameter('web_post_type_slug');
            $post_type_slug = is_object($post_type) ? $post_type->slug : $post_type;

            try {
                return Post::where('type', $post_type_slug ?: -1)
                    ->publishedOrPreview()
                    ->notHiddenOfLocale($language)
                    ->whereSlug($value)
                    ->firstOrFail();

            } catch (ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });

        Route::bind('page_slug', function ($value, $route) {
            $language = $route->parameter('language');

            try {
                return Post::where('type', 'pages')
                    ->published()
                    ->notHiddenOfLocale($language)
                    ->whereSlug($value)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                abort(404);
            }

            return $value;
        });
    }

    protected function generateMigrationName(string $migrationFileName, Carbon $now): string
    {
        $migrationsPath = 'migrations/' . dirname($migrationFileName) . '/';
        $migrationFileName = basename($migrationFileName);

        $len = strlen($migrationFileName) + 4;

        if (Str::contains($migrationFileName, '/')) {
            $migrationsPath .= Str::of($migrationFileName)->beforeLast('/')->finish('/');
            $migrationFileName = Str::of($migrationFileName)->afterLast('/');
        }

        foreach (glob(database_path("{$migrationsPath}*.php")) as $filename) {
            if ((substr($filename, -$len) === $migrationFileName . '.php')) {
                return $filename;
            }
        }

        $timestamp = $now->format('Y_m_d_His');
        $migrationFileName = Str::of($migrationFileName)->snake()->finish('.php');

        return database_path($migrationsPath . $timestamp . '_' . $migrationFileName);
    }

    private function getPolicies(): array
    {
        $policies = [];

        foreach (config('cms.policies') as $model_name => $policy) {
            $policies[config("cms.models.{$model_name}")] = $policy;
        }

        return $policies;
    }

    public function registerBreadcrumbs()
    {

    }
}
