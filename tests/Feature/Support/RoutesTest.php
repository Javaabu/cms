<?php

namespace Javaabu\Cms\Tests\Feature\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Support\Routes;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\MenuBuilder\Menu\MenuItem;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('model');
                $table->uuid('uuid')->nullable()->unique();
                $table->string('collection_name')->nullable();
                $table->string('name');
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->string('disk')->nullable();
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->json('manipulations')->nullable();
                $table->json('custom_properties')->nullable();
                $table->json('generated_conversions')->nullable();
                $table->json('responsive_images')->nullable();
                $table->unsignedInteger('order_column')->nullable();
                $table->json('translations')->nullable();
                $table->string('lang')->nullable();
                $table->boolean('hide_translation')->default(false);
                $table->timestamps();
            });
        }
    }

    #[Test]
    public function custom_post_type_routes_are_named_and_defaulted_for_the_requested_post_type(): void
    {
        Routes::customPostType('news', prefix: 'content', middleware: []);

        $routes = collect(RouteFacade::getRoutes());
        $indexRoute = $routes->first(fn ($route) => $route->uri() === 'content/news');
        $showRoute = $routes->first(fn ($route) => $route->uri() === 'content/news/{post_slug}');
        $categoryRoute = $routes->first(fn ($route) => $route->uri() === 'content/news/category/{category}');

        $this->assertSame('content/news', $indexRoute->uri());
        $this->assertSame('post-types.news.index', $indexRoute->getName());
        $this->assertSame('news', $indexRoute->defaults['postType']);
        $this->assertSame('content/news/{post_slug}', $showRoute->uri());
        $this->assertSame('post-types.news.show', $showRoute->getName());
        $this->assertSame('news', $showRoute->defaults['postType']);
        $this->assertSame('content/news/category/{category}', $categoryRoute->uri());
        $this->assertSame('post-types.news.category', $categoryRoute->getName());
    }

    #[Test]
    public function admin_post_binding_resolves_posts_only_inside_the_current_post_type(): void
    {
        RouteFacade::name('admin.')->group(fn () => Routes::admin(prefix: null, middleware: []));

        $newsType = $this->createPostType('news');
        $blogType = $this->createPostType('blog');
        $newsPost = $this->createPost($newsType);
        $blogPost = $this->createPost($blogType);

        $route = RouteFacade::getRoutes()->match(request()->create("/news/{$newsPost->id}/edit", 'GET'));
        request()->setRouteResolver(fn () => $route);
        RouteFacade::substituteBindings($route);
        $resolvedPost = $route->parameter('post');

        $this->assertSame('admin.posts.edit', $route->getName());
        $this->assertTrue($resolvedPost->is($newsPost));

        $this->expectException(NotFoundHttpException::class);

        request()->setRouteResolver(fn () => RouteFacade::getRoutes()->match(request()->create("/news/{$blogPost->id}/edit", 'GET')));
        RouteFacade::substituteBindings(request()->route());
    }

    #[Test]
    public function admin_routes_register_media_before_dynamic_post_type_routes(): void
    {
        RouteFacade::name('admin.')->group(fn () => Routes::admin(prefix: null, middleware: []));

        $pickerRoute = RouteFacade::getRoutes()->match(request()->create('/media/picker', 'GET'));
        $indexRoute = RouteFacade::getRoutes()->match(request()->create('/media', 'GET'));

        $this->assertSame('admin.media.picker', $pickerRoute->getName());
        $this->assertSame('admin.media.index', $indexRoute->getName());
        $this->assertSame(\Javaabu\Cms\Http\Controllers\Admin\MediaController::class . '@picker', $pickerRoute->getActionName());
    }

    #[Test]
    public function admin_post_type_category_type_and_media_bindings_resolve_configured_models(): void
    {
        RouteFacade::name('admin.')->group(fn () => Routes::admin(prefix: null, middleware: []));

        $postType = $this->createPostType('reports');
        $categoryType = $this->createCategoryType('report-categories');
        $category = $this->createCategory($categoryType, 'Reports');
        $media = $this->createMediaRecord();

        $postTypeRoute = RouteFacade::getRoutes()->match(request()->create('/reports', 'GET'));
        request()->setRouteResolver(fn () => $postTypeRoute);
        RouteFacade::substituteBindings($postTypeRoute);

        $categoryRoute = RouteFacade::getRoutes()->match(request()->create("/category-types/report-categories/{$category->id}/edit", 'GET'));
        request()->setRouteResolver(fn () => $categoryRoute);
        RouteFacade::substituteBindings($categoryRoute);

        $mediaRoute = RouteFacade::getRoutes()->match(request()->create("/media/{$media->id}/edit", 'GET'));
        request()->setRouteResolver(fn () => $mediaRoute);
        RouteFacade::substituteBindings($mediaRoute);

        $this->assertTrue($postTypeRoute->parameter('post_type')->is($postType));
        $this->assertTrue($categoryRoute->parameter('category_type')->is($categoryType));
        $this->assertTrue($mediaRoute->parameter('media')->is($media));
    }

    #[Test]
    public function web_post_slug_binding_exposes_only_published_posts_for_the_current_post_type(): void
    {
        Routes::web(middleware: []);

        $newsType = $this->createPostType('news');
        $blogType = $this->createPostType('blog');
        $publishedNews = $this->createPost($newsType, [
            'slug' => 'public-story',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $this->createPost($blogType, [
            'slug' => 'public-story',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $draftNews = $this->createPost($newsType, [
            'slug' => 'draft-story',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subDay(),
        ]);

        $route = RouteFacade::getRoutes()->match(request()->create('/news/public-story', 'GET'));
        request()->setRouteResolver(fn () => $route);
        RouteFacade::substituteBindings($route);

        $this->assertTrue(request()->route('post_slug')->is($publishedNews));

        $this->expectException(NotFoundHttpException::class);

        $draftRoute = RouteFacade::getRoutes()->match(request()->create("/news/{$draftNews->slug}", 'GET'));
        request()->setRouteResolver(fn () => $draftRoute);
        RouteFacade::substituteBindings($draftRoute);
    }

    #[Test]
    public function page_slug_binding_only_resolves_published_pages_and_web_routes_honor_prefixes(): void
    {
        RouteFacade::setRoutes(new RouteCollection());
        Routes::web(prefix: 'content', middleware: []);

        $pages = $this->createPostType('pages');
        $this->createPost($pages, [
            'slug' => 'hidden-page',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subDay(),
        ]);
        $page = $this->createPost($pages, [
            'slug' => 'about-us',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $showRoute = RouteFacade::getRoutes()->match(request()->create('/content/pages/about-us', 'GET'));
        request()->setRouteResolver(fn () => $showRoute);
        RouteFacade::substituteBindings($showRoute);

        $this->assertSame('posts.show', $showRoute->getName());
        $this->assertTrue(request()->route('post_slug')->is($page));
        $this->assertTrue(
            collect(RouteFacade::getRoutes())->contains(
                fn ($route) => $route->uri() === 'content/{postType}/category/{category}' && $route->getName() === 'posts.category'
            )
        );

        $this->expectException(NotFoundHttpException::class);

        $missingRoute = RouteFacade::getRoutes()->match(request()->create('/content/pages/hidden-page', 'GET'));
        request()->setRouteResolver(fn () => $missingRoute);
        RouteFacade::substituteBindings($missingRoute);
    }

    #[Test]
    public function admin_category_binding_resolves_categories_only_inside_the_current_category_type(): void
    {
        RouteFacade::name('admin.')->group(fn () => Routes::admin(prefix: null, middleware: []));

        $newsType = $this->createCategoryType('news-categories');
        $blogType = $this->createCategoryType('blog-categories');
        $newsCategory = $this->createCategory($newsType, 'News');
        $blogCategory = $this->createCategory($blogType, 'Blog');

        $route = RouteFacade::getRoutes()->match(request()->create("/category-types/news-categories/{$newsCategory->id}/edit", 'GET'));
        request()->setRouteResolver(fn () => $route);
        RouteFacade::substituteBindings($route);

        $this->assertTrue(request()->route('category')->is($newsCategory));

        $this->expectException(NotFoundHttpException::class);

        $wrongRoute = RouteFacade::getRoutes()->match(request()->create("/category-types/news-categories/{$blogCategory->id}/edit", 'GET'));
        request()->setRouteResolver(fn () => $wrongRoute);
        RouteFacade::substituteBindings($wrongRoute);
    }

    #[Test]
    public function admin_side_bar_menu_items_build_children_links_and_pending_counts(): void
    {
        RouteFacade::setRoutes(new RouteCollection());
        RouteFacade::get('/admin/{post_type}', fn () => 'posts')->name('admin.posts.index');
        RouteFacade::get('/admin/categories/{category_type}', fn () => 'categories')->name('admin.categories.index');
        RouteFacade::getRoutes()->refreshNameLookups();

        config()->set('cms.should_translate', false);
        config()->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', RoutesMenuUser::class);

        $categoryType = $this->createCategoryType('news-categories');
        $news = $this->createPostType('news');
        $news->features = ['categories' => true];
        $news->categoryType()->associate($categoryType);
        $news->save();

        $pages = $this->createPostType('pages');
        $pages->features = [];
        $pages->save();

        $this->createPost($news, [
            'title' => 'Pending Story',
            'slug' => 'pending-story',
            'status' => PostStatus::PENDING->value,
            'published_at' => now(),
        ]);

        $request = request()->create('/admin/news', 'GET');
        $route = RouteFacade::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $menus = Routes::adminSideBarMenuItems();

        $this->assertContainsOnlyInstancesOf(MenuItem::class, $menus);

        $newsMenu = collect($menus)->first(fn (MenuItem $item) => $item->getLabel() === 'News');
        $pagesMenu = collect($menus)->first(fn (MenuItem $item) => $item->getLabel() === 'Pages');

        $this->assertNotNull($newsMenu);
        $this->assertCount(2, $newsMenu->getChildren());
        $this->assertSame(route('admin.posts.index', $news->slug), $newsMenu->getChildren()[0]->getLink());
        $this->assertSame(route('admin.categories.index', 'news-categories'), $newsMenu->getChildren()[1]->getLink());
        $this->assertSame(0, $newsMenu->getChildren()[0]->getCount(new RoutesMenuUser(['view_news'])));
        $this->assertTrue($newsMenu->canView(new RoutesMenuUser(['view_news', 'view_news_categories'])));

        $this->assertNotNull($pagesMenu);
        $this->assertSame(route('admin.posts.index', $pages->slug), $pagesMenu->getLink());
    }

    private function createPostType(string $slug): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
            'icon' => 'ri-file-line',
            'features' => [],
        ]);

        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }

    private function createPost(PostType $postType, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-' . fake()->unique()->numberBetween(1, 999999),
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));

        $post->lang = $attributes['lang'] ?? 'en';
        $post->save();

        return $post;
    }

    private function createCategoryType(string $slug): CategoryType
    {
        $categoryType = new CategoryType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
        ]);

        $categoryType->lang = 'en';
        $categoryType->save();

        return $categoryType;
    }

    private function createCategory(CategoryType $type, string $name): Category
    {
        $category = new Category([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);

        $category->type_id = $type->id;
        $category->lang = 'en';
        $category->save();

        return $category;
    }

    private function createMediaRecord(): Media
    {
        $media = new Media([
            'name' => 'Route Test Media',
        ]);
        $media->file_name = 'route-test.pdf';
        $media->mime_type = 'application/pdf';
        $media->disk = 'public';
        $media->conversions_disk = 'public';
        $media->size = 100;
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = [];
        $media->responsive_images = [];
        $media->collection_name = 'documents';
        $media->model_type = self::class;
        $media->model_id = 1;
        $media->save();

        return $media;
    }
}

class RoutesMenuUser extends \Illuminate\Foundation\Auth\User
{
    public function __construct(private array $permissions = [])
    {
        parent::__construct();
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
