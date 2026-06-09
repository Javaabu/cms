<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Enums\RootSlugs\RootSlugsRegistrar;
use Javaabu\Cms\Enums\RootSlugs\RootSlugsServiceProvider;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RootSlugsServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_short_archive_routes_for_cached_root_slugs_and_skips_empty_sets(): void
    {
        Route::setRoutes(new RouteCollection());

        $provider = new RootSlugsServiceProvider($this->app);
        $registrar = new class (app('cache')) extends RootSlugsRegistrar {
            public function __construct($cacheManager)
            {
                parent::__construct($cacheManager);
            }

            public function getSlugs(): ?array
            {
                $postType = new PostType([
                    'name' => 'News',
                    'singular_name' => 'News',
                    'slug' => 'news',
                    'icon' => 'ri-news-line',
                ]);

                return ['post_type' => collect([$postType])];
            }
        };

        $provider->boot($registrar, app('files'));
        $provider->registerRoutes();

        $routes = collect(Route::getRoutes());
        $shortArchive = $routes->first(fn ($route) => $route->uri() === 'news');

        $this->assertSame('web.short-archive.news', $shortArchive->getName());
        $this->assertSame('news', $shortArchive->defaults['archive']);

        Route::setRoutes(new RouteCollection());
        $emptyProvider = new RootSlugsServiceProvider($this->app);
        $emptyProvider->boot(new class (app('cache')) extends RootSlugsRegistrar {
            public function __construct($cacheManager)
            {
                parent::__construct($cacheManager);
            }

            public function getSlugs(): ?array
            {
                return null;
            }
        }, app('files'));
        $emptyProvider->registerRoutes();

        $this->assertCount(0, Route::getRoutes());
        $this->assertInstanceOf(RootSlugsRegistrar::class, app(RootSlugsRegistrar::class));
    }
}
