<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Dashboard\Dashboard;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Tests\TestSupport\Http\CustomDashboardController;
use Javaabu\Cms\Tests\TestSupport\Http\CustomHomeController;
use Javaabu\Cms\Tests\TestSupport\Policies\AllowAllPolicy;
use Javaabu\Permissions\Permissions;
use PHPUnit\Framework\Attributes\Test;

/**
 * The CMS admin for two user types at once: admins (web_admin) at /admin and
 * users (web) at /portal, with their own permissions
 */
class AuthorizationTest extends TestCase
{
    use InteractsWithCms;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cms.controllers.home', CustomHomeController::class);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function () {
            Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
            Route::cmsAdmin([
                'guard' => 'web',
                'prefix' => 'portal',
                'name' => 'portal.',
                'controllers' => ['dashboard' => CustomDashboardController::class],
                'roles' => false,
            ]);
            Route::cms();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();
    }

    #[Test]
    public function each_guard_uses_its_own_users_and_permissions(): void
    {
        $admin = $this->admin(['view_dashboard', 'view_news']);
        $user = $this->user(['view_dashboard', 'view_pages']);

        $this->actingAs($admin, 'web_admin')->getJson('/admin')->assertOk()->assertJsonPath('data.post_types.0.key', 'news');
        $this->actingAs($user, 'web')->getJson('/portal')->assertOk()->assertJsonPath('data.post_types.0.key', 'page');

        $this->assertSame(route('portal.page.index'), $this->getJson('/portal')->json('data.post_types.0.index_url'));
    }

    #[Test]
    public function users_cant_use_the_admins_admin(): void
    {
        $this->actingAs($this->user(['view_dashboard']), 'web')->getJson('/admin')->assertUnauthorized();
    }

    #[Test]
    public function admins_cant_use_the_users_admin(): void
    {
        $this->actingAs($this->admin(['view_dashboard']), 'web_admin')->getJson('/portal')->assertUnauthorized();
    }

    #[Test]
    public function the_controller_can_be_replaced_per_route_or_in_config(): void
    {
        $this->actingAs($this->user(['view_dashboard']), 'web')
            ->getJson('/portal')
            ->assertOk()
            ->assertJsonPath('data.greeting', 'އައްސަލާމު ޢަލައިކުމް');

        $this->actingAs($this->admin(['view_dashboard']), 'web_admin')
            ->getJson('/admin')
            ->assertOk()
            ->assertJsonMissingPath('data.greeting');

        $this->getJson('/')->assertOk()->assertJsonPath('data.banner', 'ބެނަރު');
    }

    #[Test]
    public function the_navigation_links_to_each_guards_own_routes(): void
    {
        $this->actingAs($this->user(['view_dashboard', 'view_pages']), 'web')
            ->get('/portal')
            ->assertOk()
            ->assertSee(route('portal.page.index'), false)
            ->assertDontSee(route('admin.page.index') . '"', false);
    }

    #[Test]
    public function the_dashboard_policy_comes_from_config(): void
    {
        config(['permissions.policies' => [Dashboard::class => AllowAllPolicy::class]]);

        Permissions::applyPolicies();

        $this->actingAs($this->admin(), 'web_admin')->getJson('/admin')->assertOk();
    }
}
