<?php

namespace Javaabu\Cms;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\Tag;
use Javaabu\Cms\Policies\CategoryPolicy;
use Javaabu\Cms\Policies\CategoryTypePolicy;
use Javaabu\Cms\Policies\PostPolicy;
use Javaabu\Cms\Policies\PostTypePolicy;
use Javaabu\Cms\Console\Commands\SetupCmsCommand;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the package.
     *
     * @var array
     */
    protected $policies = [
        'category_type' => CategoryTypePolicy::class,
        'category' => CategoryPolicy::class,
        'post_type' => PostTypePolicy::class,
        'post' => PostPolicy::class,
        'media' => \Javaabu\Cms\Policies\MediaPolicy::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        // Register policies
        $this->registerPolicies();

        Relation::enforceMorphMap([
            'post_type'      => config('cms.models.post_type', PostType::class),
            'post'           => config('cms.models.post', Post::class),
            'category'       => config('cms.models.category', Category::class),
            'category_type'  => config('cms.models.category_type', CategoryType::class),
            'tag'            => config('cms.models.tag', Tag::class),
            'media'          => \Javaabu\Cms\Media\Media::class,
        ]);

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cms');

        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms.php' => config_path('cms.php'),
            ], 'cms-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'cms-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/cms'),
            ], 'cms-views');

            // Publish assets - JS blocks and media related SASS
            $this->publishes([
                __DIR__ . '/../resources/js/blocks' => resource_path('js/vendor/cms/blocks'),
                __DIR__ . '/../resources/sass/media/_media-library.scss' => resource_path('sass/admin/inc/_media-library.scss'),
                __DIR__ . '/../resources/sass/media/_fileinput-overrides.scss' => resource_path('sass/admin/inc/_fileinput-overrides.scss'),
            ], 'cms-assets');
        }
    }

    /**
     * Register the package's policies.
     *
     * @return void
     */
    protected function registerPolicies()
    {
        foreach ($this->policies as $key => $policy) {
            $model = $key == 'media' ? \Javaabu\Cms\Media\Media::class : config("cms.models.$key");

            if ($model) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {

        // Require helpers defined on the package.
        require_once __DIR__ . '/Helpers/helpers.php';

        // Merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/cms.php', 'cms');

        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                SetupCmsCommand::class,
            ]);
        }

        // Register facades or singletons if needed
        $this->app->register(\Javaabu\Cms\Enums\RootSlugs\RootSlugsServiceProvider::class);
    }
}





