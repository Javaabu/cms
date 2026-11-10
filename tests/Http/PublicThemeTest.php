<?php

namespace Javaabu\Cms\Tests\Http;

use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\DynamicMenubuilder\Models\Menu;
use Javaabu\DynamicMenubuilder\Support\MenuTree;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Translatable\Middleware\LocaleMiddleware;
use Javaabu\Widgets\Facades\Widgets;
use PHPUnit\Framework\Attributes\Test;

class PublicThemeTest extends TestCase
{
    use InteractsWithCms;

    #[Test]
    public function the_home_page_lists_the_latest_news(): void
    {
        $this->createPost('news', ['title' => 'ފުރަތަމަ ޚަބަރު', 'slug' => 'first']);
        $this->createPost('news', ['title' => 'ޑްރާފްޓް', 'slug' => 'draft', 'status' => 'draft']);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="cms-theme"', false)
            ->assertSee('ފުރަތަމަ ޚަބަރު')
            ->assertDontSee('ޑްރާފްޓް')
            ->assertSee(route('news.index'), false);

        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('data.page', null)
            ->assertJsonPath('data.post_type.key', 'news')
            ->assertJsonCount(1, 'data.posts');
    }

    #[Test]
    public function the_home_page_can_show_a_page(): void
    {
        $page = $this->createPost('page', [
            'title' => 'މަރުޙަބާ',
            'slug' => 'welcome',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހިރާއްޖެ']]]]],
        ]);

        Settings::set(['cms_front_page' => $page->getKey(), 'cms_home_post_type' => 'news']);

        $this->get('/')
            ->assertOk()
            ->assertSee('މަރުޙަބާ')
            ->assertSee('ދިވެހިރާއްޖެ');

        $this->getJson('/')->assertJsonPath('data.page.id', $page->getKey());
    }

    #[Test]
    public function the_theme_shows_the_site_name_menus_and_widgets(): void
    {
        Settings::set(['cms_site_name' => 'ޖާވާބު', 'cms_tagline' => 'ޓެގްލައިން', 'cms_footer_text' => 'ފޫޓަރު']);

        $menu = Menu::create(['name' => 'Main']);
        $menu->assignLocations(['header']);
        MenuTree::sync($menu, [['key' => 'home', 'type' => 'link', 'title' => 'ފުރަތަމަ ސަފްހާ', 'url' => '/']]);

        Widgets::model()::create(['location' => 'sidebar', 'type' => 'text', 'order' => 0, 'settings' => ['title' => 'ވިޖެޓް', 'body' => 'ލިޔުން']]);

        $this->createPost('news', ['title' => 'ޚަބަރު', 'slug' => 'hello']);

        $this->get('/news')
            ->assertOk()
            ->assertSee('ޖާވާބު')
            ->assertSee('ޓެގްލައިން')
            ->assertSee('ފޫޓަރު')
            ->assertSee('ފުރަތަމަ ސަފްހާ')
            ->assertSee('ވިޖެޓް')
            ->assertSee('cms-theme__body--sidebar', false)
            ->assertSee('<html lang="en" dir="ltr">', false);
    }

    #[Test]
    public function without_sidebar_widgets_the_content_is_full_width(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('cms-theme__body--sidebar', false)
            ->assertDontSee('cms-theme__nav', false);
    }

    #[Test]
    public function the_theme_is_right_to_left_in_dhivehi(): void
    {
        if (class_exists(LocaleMiddleware::class)) {
            $this->withoutMiddleware(LocaleMiddleware::class);
        }

        app()->setLocale('dv');

        $this->get('/')->assertOk()->assertSee('<html lang="dv" dir="rtl">', false);
    }

    #[Test]
    public function the_recent_posts_and_terms_widgets_render(): void
    {
        $model = PostTypes::termModel();
        $local = $model::create(['taxonomy' => 'categories', 'name' => 'ރާއްޖެ', 'slug' => 'local']);
        $model::create(['taxonomy' => 'categories', 'name' => 'ބޭރު', 'slug' => 'world']);

        $this->createPost('news', ['title' => 'އެންމެ ފަހުގެ', 'slug' => 'latest', 'taxonomies' => ['categories' => [$local->id]]]);

        Widgets::model()::create(['location' => 'sidebar', 'type' => 'recent-posts', 'order' => 0, 'settings' => ['title' => 'ފަހުގެ ޚަބަރު', 'post_type' => 'news', 'count' => 3, 'show_date' => true]]);
        Widgets::model()::create(['location' => 'sidebar', 'type' => 'terms', 'order' => 1, 'settings' => ['title' => 'ބައިތައް', 'source' => 'news:categories', 'show_counts' => true, 'hide_empty' => true]]);
        Widgets::model()::create(['location' => 'sidebar', 'type' => 'terms', 'order' => 2, 'settings' => ['title' => 'ނެތް', 'source' => 'page:categories']]);

        $this->get('/news')
            ->assertOk()
            ->assertSee('ފަހުގެ ޚަބަރު')
            ->assertSee('އެންމެ ފަހުގެ')
            ->assertSee('ބައިތައް')
            ->assertSee('ރާއްޖެ')
            ->assertSee('(1)', false)
            ->assertDontSee('ބޭރު')
            ->assertDontSee('ނެތް');

        $props = json_decode(json_encode(Widgets::props(['sidebar'])), true)['sidebar'];

        $this->assertSame('RecentPosts', $props[0]['component']);
        $this->assertSame('އެންމެ ފަހުގެ', $props[0]['data']['posts'][0]['title']);
        $this->assertSame('Terms', $props[1]['component']);
        $this->assertSame(1, $props[1]['data']['terms'][0]['count']);
    }
}
