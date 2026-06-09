<?php

namespace Javaabu\Cms\Tests\Unit\Support;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HelperFunctionsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Route::setRoutes(new RouteCollection());
        Route::get('/admin/posts/{post}', fn (string $post) => $post)->name('admin.posts.show');
        Route::get('/{language}/admin/posts/{post}', fn (string $language, string $post) => "{$language}:{$post}")->name('translated.posts.show');
        Route::get('/admin/dashboard', [HelperFunctionsController::class, 'index'])->name('admin.dashboard');
        Route::get('/{language}/admin/dashboard', [HelperFunctionsController::class, 'index'])->name('translated.dashboard');
        Route::getRoutes()->refreshNameLookups();

        config()->set('app.url', 'https://example.test');
        config()->set('app.admin_domain', 'admin.example.test');
    }

    #[Test]
    public function translate_route_respects_the_translation_flag_and_locale(): void
    {
        config()->set('cms.should_translate', false);

        $this->assertStringContainsString('/admin/posts/story', translate_route('admin.posts.show', 'story'));

        config()->set('cms.should_translate', true);
        app()->setLocale('dv');

        $translated = translate_route('translated.posts.show', 'story');
        $explicit = translate_route('translated.posts.show', ['post' => 'story'], true, 'en');

        $this->assertSame(route('translated.posts.show', ['language' => 'dv', 'post' => 'story']), $translated);
        $this->assertSame(route('translated.posts.show', ['language' => 'en', 'post' => 'story']), $explicit);
    }

    #[Test]
    public function translate_action_admin_url_default_translation_and_tab_class_cover_their_branches(): void
    {
        config()->set('cms.should_translate', false);

        $this->assertStringContainsString('/admin/dashboard', translate_action(HelperFunctionsController::class . '@index'));

        config()->set('cms.should_translate', true);
        app()->setLocale('en');

        $this->assertSame(
            action([HelperFunctionsController::class, 'index'], ['language' => 'dv']),
            translate_action(HelperFunctionsController::class . '@index', [], true, 'dv')
        );
        $this->assertSame(
            action([HelperFunctionsController::class, 'index'], ['language' => 'en', 'report']),
            translate_action(HelperFunctionsController::class . '@index', ['report'])
        );
        $this->assertSame('http://admin.example.test/reports', admin_url('/reports'));
        $this->assertSame('Hello Farish', _d('Hello :name', ['name' => 'Farish'], 'en'));
        $this->assertSame('tab-pane fade active show', add_tab_class(true));
        $this->assertSame('tabs', add_tab_class(false, 'tabs', 'visible'));
    }

    #[Test]
    public function helper_functions_cover_default_locale_and_scalar_parameter_paths(): void
    {
        config()->set('cms.should_translate', true);
        app()->setLocale('dv');

        $this->assertStringContainsString('/dv/', translate_route('translated.posts.show', ['post' => 'report']));
        $this->assertStringContainsString('language=dv', translate_action(HelperFunctionsController::class . '@index', 'report'));
        $this->assertSame('tab-pane fade', add_tab_class(false));
        $this->assertSame('Hello Team', _d('Hello :name', ['name' => 'Team']));
        $this->assertSame('http://admin.example.test', admin_url('/'));
    }
}

class HelperFunctionsController
{
    public function index(): string
    {
        return 'ok';
    }
}
