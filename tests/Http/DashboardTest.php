<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Javaabu\Cms\Dashboard\Dashboard;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Tests\TestSupport\Policies\AllowAllPolicy;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends TestCase
{
    use InteractsWithCms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();
    }

    #[Test]
    public function admins_without_view_dashboard_are_forbidden(): void
    {
        $this->actingAs($this->admin(), 'web_admin')
            ->get('/admin')
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_counts_and_recent_posts_of_the_types_the_admin_can_view(): void
    {
        $this->createPost('news', ['title' => 'ޝާއިޢުކުރެވިފައިވާ ޚަބަރު']);
        $this->createPost('news', ['title' => 'ޑްރާފްޓް', 'status' => 'draft']);
        $this->createPost('news', ['title' => 'ޝެޑިއުލް', 'published_at' => Carbon::now()->addWeek()]);
        $this->createPost('page', ['title' => 'ސަފްހާ']);

        $admin = $this->admin(['view_dashboard', 'view_news', 'edit_news']);

        $response = $this->actingAs($admin, 'web_admin')->getJson('/admin')->assertOk();

        $types = collect($response->json('data.post_types'))->keyBy('key');

        $this->assertSame(['news'], $types->keys()->all());
        $this->assertSame(['published' => 1, 'scheduled' => 1, 'draft' => 1, 'pending' => 0], $types['news']['counts']);
        $this->assertSame(route('admin.news.create'), $types['news']['create_url']);
        $this->assertSame(route('admin.news.index'), $types['news']['index_url']);

        $recent = collect($response->json('data.recent'));

        $this->assertCount(3, $recent);
        $this->assertNotContains('ސަފްހާ', $recent->pluck('title'));
        $this->assertStringContainsString('/admin/news/', $recent->first()['edit_url']);
    }

    #[Test]
    public function viewers_get_no_create_or_edit_links(): void
    {
        $this->createPost('news');

        $response = $this->actingAs($this->admin(['view_dashboard', 'view_news']), 'web_admin')
            ->getJson('/admin')
            ->assertOk();

        $this->assertNull($response->json('data.post_types.0.create_url'));
        $this->assertNull($response->json('data.recent.0.edit_url'));
    }

    #[Test]
    public function it_renders_in_the_admin_layout_with_the_navigation(): void
    {
        $this->createPost('news', ['title' => 'ރާއްޖޭގެ ޚަބަރު']);

        $this->actingAs($this->admin(['view_dashboard', 'view_news', 'edit_news']), 'web_admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('class="cms-admin"', false)
            ->assertSee('ރާއްޖޭގެ ޚަބަރު')
            ->assertSee(route('admin.news.index'), false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee(route('admin.menus.index'), false);
    }

    #[Test]
    public function an_admin_without_content_permissions_sees_an_empty_dashboard(): void
    {
        $this->actingAs($this->admin(['view_dashboard']), 'web_admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee(__('You can\'t see any content yet.'));
    }

    #[Test]
    public function the_policy_can_be_overridden(): void
    {
        Gate::policy(Dashboard::class, AllowAllPolicy::class);

        $this->actingAs($this->admin(), 'web_admin')->get('/admin')->assertOk();
    }
}
