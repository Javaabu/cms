<?php

namespace Javaabu\Cms\Enums\RootSlugs;


use Illuminate\Routing\Router;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\HomeController;
use Illuminate\Contracts\Container\BindingResolutionException;
use Javaabu\Cms\Models\PostType;

class RootSlugsServiceProvider extends ServiceProvider
{
    /**
     * @param RootSlugsRegistrar $language_loader
     * @param Filesystem $filesystem
     */
    public function boot(RootSlugsRegistrar $language_loader, Filesystem $filesystem)
    {
        $this->app->singleton(RootSlugsRegistrar::class, function ($app) use ($language_loader) {
            return $language_loader;
        });

        //$this->registerRoutes();
    }

    /**
     * Register service provider
     */
    public function register()
    {
        //
    }

    /**
     * Register routes.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function registerRoutes()
    {
        $slugs = app(RootSlugsRegistrar::class)->getSlugs();

        if (! $slugs) {
            return;
        }

        /** @var Router $router */
        $router = app()->make('router');

        $listing_types = $slugs['post_type'];

        $listing_types->each(function (PostType $postType) use ($router) {
            $router->get($postType->slug, [HomeController::class, 'shortArchive'])
                   ->name('web.short-archive.' . $postType->slug)
                   ->defaults('archive', $postType->slug);

            /*$router->get('{language}/' . $postType->slug, [PostsController::class, 'index'])
                   ->defaults('web_post_type_slug', $postType->slug)
                   ->name('web.posts.index.' . $postType->slug)
                   ->middleware('web', 'language');

            $router->get('{language}/' . $postType->slug . '/{post_slug}', [PostsController::class, 'show'])
                   ->defaults('web_post_type_slug', $postType)
                   ->name('web.posts.show.' . $postType->slug)
                   ->middleware('web', 'language');*/

        });
    }
}
