<?php

namespace Javaabu\Cms\Tests\Unit\Http\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Http\Requests\CategoriesRequest;
use Javaabu\Cms\Http\Requests\CategoryTypesRequest;
use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Http\Requests\PostsRequest;
use Javaabu\Cms\Http\Requests\PostTypesRequest;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FormRequestRulesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function post_type_request_requires_core_fields_only_on_create(): void
    {
        $createRules = $this->requestRules(PostTypesRequest::class);
        $updateRules = $this->requestRules(PostTypesRequest::class, [
            'post_type' => new PostType(),
        ]);

        $this->assertStringContainsString('required', $createRules['name']);
        $this->assertStringContainsString('required', $createRules['singular_name']);
        $this->assertStringContainsString('required', $createRules['slug']);
        $this->assertStringContainsString('required', $createRules['icon']);

        $this->assertStringNotContainsString('required', $updateRules['name']);
        $this->assertStringNotContainsString('required', $updateRules['singular_name']);
        $this->assertStringNotContainsString('required', $updateRules['slug']);
        $this->assertStringNotContainsString('required', $updateRules['icon']);
    }

    #[Test]
    public function category_type_request_requires_core_fields_only_on_create(): void
    {
        $createRules = $this->requestRules(CategoryTypesRequest::class);
        $updateRules = $this->requestRules(CategoryTypesRequest::class, [
            'category_type' => new CategoryType(),
        ]);

        $this->assertStringContainsString('required', $createRules['name']);
        $this->assertStringContainsString('required', $createRules['singular_name']);
        $this->assertStringContainsString('required', $createRules['slug']);
        $this->assertStringNotContainsString('required', $updateRules['name']);
        $this->assertStringNotContainsString('required', $updateRules['singular_name']);
        $this->assertStringNotContainsString('required', $updateRules['slug']);
    }

    #[Test]
    public function categories_request_prevents_using_the_current_category_or_descendants_as_parent(): void
    {
        $type = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $type->lang = 'en';
        $type->save();

        $parent = new Category([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);
        $parent->type_id = $type->id;
        $parent->lang = 'en';
        $parent->save();

        $child = new Category([
            'name' => 'Child',
            'slug' => 'child',
        ]);
        $child->type_id = $type->id;
        $child->lang = 'en';
        $child->appendToNode($parent)->save();

        $rules = $this->requestRules(CategoriesRequest::class, [
            'type' => $type,
            'category' => $parent,
        ]);

        $this->assertStringContainsString('exists:categories,id,type_id,' . $type->id, $rules['parent']);
        $this->assertStringContainsString('not_in:', $rules['parent']);
        $this->assertStringContainsString((string) $parent->id, $rules['parent']);
        $this->assertStringContainsString((string) $child->id, $rules['parent']);
    }

    #[Test]
    public function posts_request_adds_category_rules_when_the_post_type_supports_categories(): void
    {
        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $postType = new PostType([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
            'category_type_id' => $categoryType->id,
            'features' => [PostTypeFeatures::CATEGORIES->value => true],
        ]);
        $postType->lang = 'en';
        $postType->save();

        $rules = $this->requestRules(PostsRequest::class, [
            'type' => $postType,
        ], ['published_at' => '2026-05-01 10:00:00']);

        $this->assertSame('nullable|array', $rules['categories']);
        $this->assertSame('exists:categories,id,type_id,' . $categoryType->id, $rules['categories.*']);
        $this->assertStringContainsString('required', $rules['title']);
        $this->assertSame('nullable|date|after:2026-05-01 10:00:00', $rules['expire_at']);
    }

    #[Test]
    public function post_request_uses_existing_post_publish_date_when_validating_expiry_on_update(): void
    {
        $postType = new PostType([
            'name' => 'Pages',
            'singular_name' => 'Page',
            'slug' => 'pages',
            'icon' => 'ri-file-line',
            'features' => [],
        ]);
        $postType->lang = 'en';
        $postType->save();

        $post = new Post([
            'type' => $postType->slug,
            'title' => 'About',
            'slug' => 'about',
            'status' => 'draft',
            'published_at' => '2026-05-01 10:00:00',
        ]);
        $post->lang = 'en';
        $post->save();

        $rules = $this->requestRules(PostRequest::class, [
            'post_type' => $postType,
            'post' => $post,
        ]);

        $this->assertStringNotContainsString('required', implode('|', $rules['slug']));
        $this->assertSame('nullable|date|after:2026-05-01 10:00:00', $rules['expire_at']);
        $this->assertSame(['publish', 'reject', 'draft'], (new PostRequest())->getBaseActions());
    }

    private function requestRules(string $requestClass, array $routeParameters = [], array $input = []): array
    {
        $request = $requestClass::create('/', 'POST', $input);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        $route = new Route(['POST'], '/', []);
        $route->bind($request);
        foreach ($routeParameters as $key => $value) {
            $route->setParameter($key, $value);
        }

        $request->setRouteResolver(fn () => $route);

        return $request->rules();
    }
}
