<?php

namespace Javaabu\Cms\Tests\Feature;

use Illuminate\Support\Facades\File;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\DynamicMenubuilder\Models\Menu;
use Javaabu\Permissions\Models\Permission;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\Test;

class InstallCommandTest extends TestCase
{
    use InteractsWithCms;

    protected function tearDown(): void
    {
        File::delete(config_path('cms.php'));

        parent::tearDown();
    }

    #[Test]
    public function it_publishes_the_config_syncs_permissions_and_creates_starter_content(): void
    {
        $this->artisan('cms:install')
            ->expectsOutputToContain('Route::cmsAdmin')
            ->assertSuccessful();

        $this->assertFileExists(config_path('cms.php'));
        $this->assertTrue(Permission::query()->where('name', 'view_dashboard')->where('guard_name', 'web_admin')->exists());
        $this->assertTrue(Permission::query()->where('name', 'edit_news')->exists());

        $about = PostTypes::get('page')->query()->where('slug', 'about')->first();
        $welcome = PostTypes::get('news')->query()->where('slug', 'welcome')->first();
        $general = PostTypes::termModel()::query()->where('slug', 'general')->first();

        $this->assertNotNull($about);
        $this->assertTrue($about->isPublished());
        $this->assertSame('About us', (string) $about->title);
        $this->assertNotNull($general);
        $this->assertSame([$general->id], $welcome->termsOf('categories')->pluck('id')->all());

        $header = array_map(fn ($node) => $node->title, Menus::items('header'));

        $this->assertSame(['Home', 'News', 'About us'], $header);
        $this->assertSame(['About us'], array_map(fn ($node) => $node->title, Menus::items('footer')));

        $this->assertSame(['recent-posts', 'terms'], Widgets::widgets('sidebar')->pluck('type')->all());

        $this->get('/')->assertOk()->assertSee('Welcome to your new site');
        $this->get('/about')->assertOk()->assertSee('About us');
    }

    #[Test]
    public function running_it_again_creates_nothing_new(): void
    {
        $this->artisan('cms:install')->assertSuccessful();
        $this->artisan('cms:install')
            ->expectsOutputToContain('already')
            ->assertSuccessful();

        $this->assertSame(1, PostTypes::get('page')->query()->count());
        $this->assertSame(1, PostTypes::get('news')->query()->count());
        $this->assertSame(2, Widgets::widgets('sidebar')->count());
        $this->assertSame(2, Menu::query()->count());
    }

    #[Test]
    public function content_can_be_skipped(): void
    {
        $this->artisan('cms:install', ['--no-content' => true, '--no-migrate' => true])->assertSuccessful();

        $this->assertSame(0, PostTypes::get('page')->query()->count());
    }

    #[Test]
    public function the_starter_content_is_in_dhivehi_when_the_app_is(): void
    {
        app()->setLocale('dv');

        $this->artisan('cms:install', ['--no-migrate' => true])->assertSuccessful();

        $this->assertSame('އަޅުގަނޑުމެންނާ ބެހޭ', (string) PostTypes::get('page')->query()->first()->title);
    }
}
