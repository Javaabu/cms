<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Routing\CmsRoutes;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoutesTest extends TestCase
{
    use InteractsWithCms;

    #[Test]
    public function the_admin_macro_registers_every_section_under_one_prefix_and_name(): void
    {
        foreach ([
            'admin.dashboard' => 'admin',
            'admin.news.index' => 'admin/news',
            'admin.page.index' => 'admin/page',
            'admin.taxonomies.categories.index' => 'admin/taxonomies/categories',
            'admin.media.index' => 'admin/media',
            'admin.menus.index' => 'admin/menus',
            'admin.widgets.index' => 'admin/widgets',
            'admin.settings.index' => 'admin/settings',
            'admin.roles.index' => 'admin/roles',
        ] as $name => $uri) {
            $this->assertTrue(Route::has($name), $name);
            $this->assertSame($uri, Route::getRoutes()->getByName($name)->uri(), $name);
            $this->assertContains('auth:web_admin', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }
    }

    #[Test]
    public function the_public_macro_registers_the_home_page_and_pages_last(): void
    {
        $this->assertSame('/', Route::getRoutes()->getByName('home')->uri());
        $this->assertSame('news', Route::getRoutes()->getByName('news.index')->uri());
        $this->assertSame('{post_slug}', Route::getRoutes()->getByName('page.show')->uri());

        $names = array_values(array_filter(array_map(fn ($route) => $route->getName(), Route::getRoutes()->getRoutes())));

        $this->assertSame('page.show', end($names));
        $this->assertSame(url('/'), CmsRoutes::homeUrl());
    }

    #[Test]
    public function a_page_is_found_at_the_site_root_and_news_at_its_permalink(): void
    {
        $page = $this->createPost('page', ['title' => 'ތަޢާރަފު', 'slug' => 'about']);
        $news = $this->createPost('news', ['slug' => 'hello']);

        $this->get('/about')->assertOk()->assertSee('ތަޢާރަފު');
        $this->get($news->permalink())->assertOk()->assertSee('ރާއްޖޭގެ ޚަބަރު');
        $this->get('/news')->assertOk();
        $this->get('/missing')->assertNotFound();

        $this->assertSame(url('/about'), $page->permalink());
    }

    #[Test]
    public function the_admin_and_public_routes_need_the_right_guard(): void
    {
        $this->getJson('/admin')->assertUnauthorized();
        $this->actingAs($this->user(), 'web')->getJson('/admin')->assertUnauthorized();
    }
}
