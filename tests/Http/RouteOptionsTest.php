<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Routing\CmsRoutes;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteOptionsTest extends TestCase
{
    use InteractsWithCms;

    protected function adminRouteOptions(): array
    {
        return [
            'guard' => 'web',
            'prefix' => 'cms',
            'name' => 'cms.',
            'middleware' => ['auth:web'],
            'roles' => false,
            'media' => false,
            'menus' => ['prefix' => 'cms/navigation'],
        ];
    }

    protected function publicRouteOptions(): array
    {
        return ['prefix' => 'site', 'name' => 'site.', 'home' => false, 'post_types' => ['only' => ['news']]];
    }

    #[Test]
    public function sections_can_be_left_out_or_given_their_own_options(): void
    {
        $this->assertTrue(Route::has('cms.dashboard'));
        $this->assertFalse(Route::has('cms.roles.index'));
        $this->assertFalse(Route::has('cms.media.index'));
        $this->assertSame('cms/navigation', Route::getRoutes()->getByName('cms.menus.index')->uri());

        $this->assertFalse(Route::has('site.home'));
        $this->assertTrue(Route::has('site.news.index'));
        $this->assertFalse(Route::has('site.page.show'));
        $this->assertNull(CmsRoutes::homeUrl());
    }

    #[Test]
    public function the_admin_name_is_remembered_per_guard(): void
    {
        $this->assertSame('cms.', CmsRoutes::adminName());
    }
}
