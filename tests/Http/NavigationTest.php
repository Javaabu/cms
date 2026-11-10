<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Cms\Facades\Cms;
use Javaabu\Cms\Navigation\NavigationItem;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Permissions\Models\Permission;
use PHPUnit\Framework\Attributes\Test;

class NavigationTest extends TestCase
{
    use InteractsWithCms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function links(?Model $user, ?string $current = null): array
    {
        $links = [];

        foreach (Cms::navigation()->resolve($user, 'admin.', $current) as $group) {
            foreach ($group['items'] as $item) {
                $links[$item['key']] = [...$item, 'group' => $group['key']];
            }
        }

        return $links;
    }

    #[Test]
    public function a_super_admin_sees_every_section_in_groups(): void
    {
        $admin = $this->admin();
        $admin->givePermissionTo(Permission::query()->where('guard_name', 'web_admin')->pluck('name')->all());

        $groups = Cms::navigation()->resolve($admin, 'admin.');

        $this->assertSame(['', 'content', 'appearance', 'administration'], array_column($groups, 'key'));
        $this->assertSame(['Content', 'Appearance', 'Administration'], array_slice(array_column($groups, 'label'), 1));

        $links = $this->links($admin);

        $this->assertSame(
            ['dashboard', 'post-type-page', 'post-type-news', 'taxonomy-categories', 'taxonomy-tags', 'media', 'menus', 'widgets', 'settings', 'roles'],
            array_keys($links),
        );
        $this->assertSame(route('admin.news.index'), $links['post-type-news']['url']);
        $this->assertSame('newspaper', $links['post-type-news']['icon']);
        $this->assertSame('News', $links['post-type-news']['label']);
        $this->assertSame(route('admin.taxonomies.categories.index'), $links['taxonomy-categories']['url']);
    }

    #[Test]
    public function links_are_filtered_by_permission(): void
    {
        $links = $this->links($this->admin(['view_dashboard', 'view_news', 'view_menus']));

        $this->assertSame(['dashboard', 'post-type-news', 'menus'], array_keys($links));
        $this->assertSame([], $this->links(null));
    }

    #[Test]
    public function the_current_section_is_active(): void
    {
        $links = $this->links($this->admin(['view_dashboard', 'view_news']), 'admin.news.edit');

        $this->assertTrue($links['post-type-news']['active']);
        $this->assertFalse($links['dashboard']['active']);

        $links = $this->links($this->admin(['view_dashboard', 'view_news']), 'admin.dashboard');

        $this->assertTrue($links['dashboard']['active']);
        $this->assertFalse($links['post-type-news']['active']);
    }

    #[Test]
    public function apps_add_change_and_remove_links(): void
    {
        Cms::navigation()
            ->group('reports', 'Reports', 25)
            ->add(
                NavigationItem::make('visits')->label('Visits')->url('https://example.com/visits')->group('reports')->icon('chart-column'),
                NavigationItem::make('secret')->label('Secret')->url('/secret')->can(fn ($user) => false),
                NavigationItem::make('broken')->label('Broken')->route('missing.index'),
                NavigationItem::make('dashboard')->label('Home')->route('dashboard')->order(0),
                NavigationItem::make('help')->label('Help')->children([
                    NavigationItem::make('docs')->label('Docs')->url('https://docs.javaabu.com'),
                ]),
            )
            ->remove('post-type-page');

        $admin = $this->admin(['view_news', 'view_pages']);
        $links = $this->links($admin);

        $this->assertSame('Home', $links['dashboard']['label']);
        $this->assertSame('reports', $links['visits']['group']);
        $this->assertArrayNotHasKey('secret', $links);
        $this->assertArrayNotHasKey('broken', $links);
        $this->assertArrayNotHasKey('post-type-page', $links);
        $this->assertNull($links['help']['url']);
        $this->assertSame('https://docs.javaabu.com', $links['help']['children'][0]['url']);
        $this->assertTrue(Cms::navigation()->has('visits'));
        $this->assertNull(Cms::navigation()->get('post-type-page'));
    }

    #[Test]
    public function the_blade_component_renders_the_links(): void
    {
        $this->actingAs($this->admin(['view_dashboard', 'view_news']), 'web_admin');

        $html = $this->blade('<x-cms::admin-navigation guard="web_admin" />');

        $html->assertSee(route('admin.news.index'), false)
            ->assertSee('data-icon="newspaper"', false)
            ->assertDontSee(route('admin.settings.index'), false);
    }
}
