<?php

namespace Javaabu\Cms\Tests\Feature\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Support\Routes;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

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
}
