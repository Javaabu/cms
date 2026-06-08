<?php

namespace Javaabu\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Cms;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\PostTypes\PostType as FluentPostType;
use Javaabu\Cms\RootSlugsRegistrar;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Http\Controllers\Admin\CategoriesController as AdminCategoriesController;
use Javaabu\Cms\Http\Controllers\Admin\PostsController as AdminPostsController;
use Javaabu\Cms\Http\Controllers\PostsController as WebPostsController;
use Javaabu\MenuBuilder\Menu\MenuItem;
use PHPUnit\Framework\Attributes\Test;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_configures_mediapicker_to_use_cms_media_classes_by_default(): void
    {
        $this->assertSame(\Javaabu\Cms\Media\Media::class, config('mediapicker.media_model'));
        $this->assertSame(\Javaabu\Cms\Http\Controllers\Admin\MediaController::class, config('mediapicker.media_controller'));
        $this->assertSame(\Javaabu\Cms\Media\Media::class, config('media-library.media_model'));
    }

    #[Test]
    public function it_registers_post_types_with_default_configuration(): void
    {
        (new Cms())->registerPostTypes([
            'pages' => [
                'name' => 'Pages',
                'name_singular' => 'Page',
                'icon' => 'ri-file-line',
                'features' => ['page-style' => true],
            ],
        ]);

        $postType = PostType::whereSlug('pages')->firstOrFail();

        $this->assertSame('Pages', $postType->name);
        $this->assertSame('Page', $postType->singular_name);
        $this->assertSame('en', $postType->lang);
        $this->assertSame('en', $postType->categoryType->lang);
        $this->assertSame(['page-style' => true], $postType->features);
    }

    #[Test]
    public function it_registers_post_types_from_fluent_configuration_and_keeps_registration_idempotent(): void
    {
        config()->set('cms.should_translate', true);

        $cms = new Cms();

        $cms->registerPostTypes([
            FluentPostType::make('news')
                ->name('News')
                ->singularName('News Article')
                ->icon('ri-news-line')
                ->features(PostTypeFeatures::CATEGORIES->value, 'excerpt'),
            'pages' => [
                'name' => 'Pages',
                'name_singular' => 'Page',
                'icon' => 'ri-file-line',
                'features' => ['page-style' => true],
            ],
        ]);

        $this->assertDatabaseCount('post_types', 2);
        $this->assertDatabaseCount('category_types', 2);

        $news = PostType::whereSlug('news')->firstOrFail();
        $this->assertSame('News', $news->name);
        $this->assertSame('News Article', $news->singular_name);
        $this->assertSame('ri-news-line', $news->icon);
        $this->assertSame([
            'categories' => true,
            'excerpt' => true,
        ], $news->features);
        $this->assertSame(0, $news->order_column);
        $this->assertSame('news-categories', $news->categoryType->slug);

        $pages = PostType::whereSlug('pages')->firstOrFail();
        $this->assertSame(1, $pages->order_column);
        $this->assertSame(['page-style' => true], $pages->features);

        $cms->registerPostTypes([
            'news' => [
                'name' => 'Latest News',
                'name_singular' => 'Latest News Item',
                'icon' => 'ri-megaphone-line',
                'features' => ['documents' => true],
            ],
        ]);

        $news->refresh();

        $this->assertDatabaseCount('post_types', 2);
        $this->assertDatabaseCount('category_types', 2);
        $this->assertSame('Latest News', $news->name);
        $this->assertSame('Latest News Item', $news->singular_name);
        $this->assertSame('ri-megaphone-line', $news->icon);
        $this->assertSame(['documents' => true], $news->features);
    }

    #[Test]
    public function it_sets_default_language_when_registering_post_types_with_translation_enabled(): void
    {
        config()->set('cms.should_translate', true);
        config()->set('cms.default_language', 'dv');

        (new Cms())->registerPostTypes([
            'announcements' => [
                'name' => 'Announcements',
                'name_singular' => 'Announcement',
                'icon' => 'ri-notification-line',
                'features' => [],
            ],
        ]);

        $this->assertSame('dv', PostType::whereSlug('announcements')->value('lang'));
    }

    #[Test]
    public function it_seeds_post_type_permissions_without_overwriting_existing_entries(): void
    {
        $postType = new PostType([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'press-releases',
            'icon' => 'ri-newspaper-line',
            'features' => [],
        ]);
        $postType->lang = 'en';
        $postType->save();

        $permissions = (new Cms())->seedPostTypePermissions([
            $postType->permission_slug => [
                'custom_press_releases' => 'Custom permission',
            ],
            'unrelated' => [
                'view_unrelated' => 'View unrelated',
            ],
        ]);

        $this->assertSame('Custom permission', $permissions['press_releases']['custom_press_releases']);
        $this->assertSame('Edit own press releases', $permissions['press_releases']['edit_press_releases']);
        $this->assertSame('Publish all press releases', $permissions['press_releases']['publish_others_press_releases']);
        $this->assertSame(['view_unrelated' => 'View unrelated'], $permissions['unrelated']);
    }

    #[Test]
    public function it_seeds_category_type_permissions_without_overwriting_existing_entries(): void
    {
        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $permissions = (new Cms())->seedCategoryTypePermissions([
            $categoryType->permission_slug => [
                'custom_news_categories' => 'Custom permission',
            ],
        ]);

        $this->assertSame('Custom permission', $permissions['news_categories']['custom_news_categories']);
        $this->assertSame('Edit news categories', $permissions['news_categories']['edit_news_categories']);
        $this->assertSame('Delete news categories', $permissions['news_categories']['delete_news_categories']);
        $this->assertSame('View news categories', $permissions['news_categories']['view_news_categories']);
    }

    #[Test]
    public function it_registers_normal_public_routes_for_non_root_post_types(): void
    {
        Route::setRoutes(new RouteCollection());

        $news = $this->createPostType('news', ['categories' => true]);
        $pages = $this->createPostType('pages', ['root-page' => true]);

        app()->instance(RootSlugsRegistrar::class, new class ($news, $pages) {
            public function __construct(private PostType $news, private PostType $pages) {}
            public function getSlugs(): array
            {
                return ['post_type' => collect([$this->news])];
            }
        });

        config()->set('cms.web.controllers.posts', WebPostsController::class);

        (new Cms())->registerNormalRoutes();

        $routes = collect(Route::getRoutes());
        $index = $routes->first(fn ($route) => $route->uri() === 'news');
        $show = $routes->first(fn ($route) => $route->uri() === 'news/{post_slug}');

        $this->assertSame('index.news', $index->getName());
        $this->assertSame('show.news', $show->getName());
        $this->assertSame(WebPostsController::class . '@index', $index->getActionName());
        $this->assertSame(WebPostsController::class . '@show', $show->getActionName());
    }

    #[Test]
    public function it_registers_normal_admin_routes_for_categories_and_posts(): void
    {
        Route::setRoutes(new RouteCollection());

        config()->set('cms.admin.controllers.categories', AdminCategoriesController::class);
        config()->set('cms.admin.controllers.posts', AdminPostsController::class);

        (new Cms())->registerNormalAdminRoutes();

        $routes = collect(Route::getRoutes());

        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === 'category-types/{category_type}' && $route->getName() === 'categories.index'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === 'category-types/{category_type}/{category}/edit' && $route->getName() === 'categories.edit'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{post_type}' && $route->getName() === 'posts.index'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{post_type}/{post}/edit' && $route->getName() === 'posts.edit'));
    }

    #[Test]
    public function it_registers_translatable_public_and_admin_routes_under_a_language_prefix(): void
    {
        Route::setRoutes(new RouteCollection());

        $news = $this->createPostType('news', ['categories' => true]);
        app()->instance(RootSlugsRegistrar::class, new class ($news) {
            public function __construct(private PostType $news) {}
            public function getSlugs(): array
            {
                return ['post_type' => collect([$this->news])];
            }
        });

        config()->set('cms.web.controllers.posts', WebPostsController::class);
        config()->set('cms.admin.controllers.categories', AdminCategoriesController::class);
        config()->set('cms.admin.controllers.posts', AdminPostsController::class);

        $cms = new Cms();
        $cms->registerTranslatableRoutes();
        $cms->registerTranslatableAdminRoutes();

        $routes = collect(Route::getRoutes());

        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{language}/news' && $route->getName() === 'index.news'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{language}/news/{post_slug}' && $route->getName() === 'show.news'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{language}/category-types/{category_type}' && $route->getName() === 'categories.index'));
        $this->assertTrue($routes->contains(fn ($route) => $route->uri() === '{language}/{post_type}/{post}/edit' && $route->getName() === 'posts.edit'));
    }

    #[Test]
    public function it_builds_admin_menu_items_with_category_children_and_active_states(): void
    {
        Route::setRoutes(new RouteCollection());
        Route::get('/admin/{post_type}', fn () => 'posts')->name('admin.posts.index');
        Route::get('/admin/categories/{category_type}', fn () => 'categories')->name('admin.categories.index');
        Route::getRoutes()->refreshNameLookups();

        config()->set('cms.should_translate', false);
        config()->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);

        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $news = $this->createPostType('news', [PostTypeFeatures::CATEGORIES->value => true]);
        $news->categoryType()->associate($categoryType);
        $news->save();

        $page = $this->createPostType('pages', []);

        $pending = new Post([
            'type' => $news->slug,
            'title' => 'Pending Story',
            'slug' => 'pending-story',
            'status' => 'pending',
            'published_at' => now(),
        ]);
        $pending->lang = 'en';
        $pending->save();

        $request = request()->create('/admin/news', 'GET');
        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $menus = (new Cms())->adminMenuItems();

        $this->assertCount(2, $menus);
        $this->assertContainsOnlyInstancesOf(MenuItem::class, $menus);

        $newsMenu = collect($menus)->first(fn (MenuItem $item) => $item->getLabel() === 'News');
        $pageMenu = collect($menus)->first(fn (MenuItem $item) => $item->getLabel() === 'Pages');

        $this->assertNotNull($newsMenu);
        $this->assertCount(2, $newsMenu->getChildren());
        $this->assertSame('zmdi-' . $news->icon, $newsMenu->getIcon());
        $this->assertTrue($newsMenu->canView(new CmsMenuUser(['view_news', 'view_news_categories'])));
        $this->assertSame(route('admin.posts.index', $news->slug), $newsMenu->getChildren()[0]->getLink());
        $this->assertSame(route('admin.categories.index', 'news-categories'), $newsMenu->getChildren()[1]->getLink());
        $this->assertSame(0, $newsMenu->getChildren()[0]->getCount(new CmsMenuUser(['view_news'])));

        $this->assertNotNull($pageMenu);
        $this->assertSame(route('admin.posts.index', $page->slug), $pageMenu->getLink());
    }

    private function createPostType(string $slug, array $features): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
            'icon' => 'ri-file-line',
            'features' => $features,
        ]);

        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }
}

class CmsMenuUser extends \Illuminate\Foundation\Auth\User
{
    public function __construct(private array $permissions = [], array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function can($ability, $arguments = []): bool
    {
        return in_array($ability, $this->permissions, true);
    }

    public function canAny($abilities, $arguments = []): bool
    {
        foreach ($abilities as $ability) {
            if (in_array($ability, $this->permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
