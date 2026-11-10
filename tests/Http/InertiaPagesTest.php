<?php

namespace Javaabu\Cms\Tests\Http;

use Illuminate\Support\Carbon;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\DynamicMenubuilder\Models\Menu;
use Javaabu\DynamicMenubuilder\Support\MenuTree;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Inertia pages' props. With CMS_UPDATE_FIXTURES=1 it writes them to
 * tests/js/fixtures, which the React tests render.
 */
#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithCms;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cms.views', 'inertia');
        $app['view']->addLocation(__DIR__ . '/../TestSupport/views');
        $app['config']->set('inertia.root_view', 'app');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();

        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function writeFixture(string $name, array $props): void
    {
        unset($props['errors']);

        if (getenv('CMS_UPDATE_FIXTURES')) {
            file_put_contents(
                __DIR__ . "/../js/fixtures/{$name}.json",
                json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }
    }

    #[Test]
    public function it_renders_the_dashboard_with_the_navigation(): void
    {
        $this->createPost('news', ['title' => 'ރާއްޖޭގެ ޚަބަރު', 'slug' => 'local', 'published_at' => '2026-11-01 08:00']);
        $this->createPost('news', ['title' => 'ޑްރާފްޓް', 'slug' => 'draft', 'status' => 'draft']);

        $response = $this->actingAs($this->admin(['view_dashboard', 'view_news', 'edit_news', 'view_menus']), 'web_admin')
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cms::Admin/Dashboard')
                ->where('post_types.0.key', 'news')
                ->where('post_types.0.counts.draft', 1)
                ->where('recent.0.title', 'ޑްރާފްޓް')
                ->where('strings.title', 'Dashboard')
                ->where('cms.area', 'admin')
                ->where('cms.site.name', 'Javaabu')
                ->where('cms.navigation.0.items.0.key', 'dashboard')
                ->where('cms.navigation.0.items.0.active', true)
                ->where('cms.navigation.1.key', 'content')
                ->where('cms.navigation.2.items.0.key', 'menus')
                ->missing('widgets'));

        $this->writeFixture('dashboard', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_home_page_with_menus_and_widgets(): void
    {
        Settings::set(['cms_site_name' => 'ޖާވާބު', 'cms_tagline' => 'ޓެގްލައިން']);

        $menu = Menu::create(['name' => 'Main']);
        $menu->assignLocations(['header']);
        MenuTree::sync($menu, [['key' => 'home', 'type' => 'link', 'title' => 'ފުރަތަމަ ސަފްހާ', 'url' => '/']]);

        Widgets::model()::create(['location' => 'sidebar', 'type' => 'recent-posts', 'order' => 0, 'settings' => ['title' => 'ފަހުގެ ޚަބަރު', 'post_type' => 'news', 'count' => 3, 'show_date' => true]]);

        $this->createPost('news', ['title' => 'ރާއްޖޭގެ ޚަބަރު', 'slug' => 'local', 'published_at' => '2026-11-01 08:00']);

        $response = $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cms::Public/Home')
                ->where('page', null)
                ->where('post_type.key', 'news')
                ->where('posts.0.title', 'ރާއްޖޭގެ ޚަބަރު')
                ->where('site.name', 'ޖާވާބު')
                ->where('cms.area', 'public')
                ->where('cms.navigation', [])
                ->where('menus.header.0.title', 'ފުރަތަމަ ސަފްހާ')
                ->where('widgets.sidebar.0.component', 'RecentPosts')
                ->where('widgets.sidebar.0.data.posts.0.title', 'ރާއްޖޭގެ ޚަބަރު'));

        $this->writeFixture('home', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_home_page_with_a_front_page(): void
    {
        $page = $this->createPost('page', [
            'title' => 'މަރުޙަބާ',
            'slug' => 'welcome',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހިރާއްޖެ']]]]],
        ]);

        Settings::set(['cms_front_page' => $page->getKey()]);

        $response = $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('cms::Public/Home')
                ->where('page.title', 'މަރުޙަބާ')
                ->where('posts', []));

        $this->writeFixture('home-page', $response->viewData('page')['props']);
    }

    #[Test]
    public function package_pages_under_the_cms_routes_get_the_shared_props(): void
    {
        $this->createPost('news', ['title' => 'ރާއްޖޭގެ ޚަބަރު', 'slug' => 'local']);

        $this->get('/news')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Public/Archive')
                ->where('cms.area', 'public')
                ->has('widgets'));

        $this->actingAs($this->admin(['view_news']), 'web_admin')
            ->get('/admin/news')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Posts/Index')
                ->where('cms.area', 'admin')
                ->where('cms.navigation.0.items.0.key', 'post-type-news'));
    }
}
