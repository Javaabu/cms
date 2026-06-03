<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Http\Controllers\Admin\PostsController as AdminPostsController;
use Javaabu\Cms\Http\Requests\PostsRequest;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminPostsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Gate::shouldReceive('authorize')->andReturn(true);

        Route::get('/_test/admin/{post_type}', fn () => 'index')->name('admin.posts.index');
        Route::get('/_test/admin/{post_type}/trash', fn () => 'trash')->name('admin.posts.trash');
        Route::get('/_test/admin/{post_type}/{post}/edit', fn () => 'edit')->name('admin.posts.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function posts_index_applies_filters_and_returns_index_view_model(): void
    {
        $type = $this->createPostType('alerts');
        $matching = $this->createPost($type, [
            'title' => 'Emergency Alert',
            'slug' => 'emergency-alert',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now(),
            'lang' => 'en',
            'translations' => ['title' => 'Translated Emergency Alert'],
        ]);
        $this->createPost($type, [
            'title' => 'Draft Alert',
            'slug' => 'draft-alert',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
            'lang' => 'dv',
        ]);

        $request = Request::create('/admin/alerts', 'GET', [
            'search' => 'Emergency',
            'status' => PostStatus::PUBLISHED->value,
            'primary_language' => 'en',
            'orderby' => 'title',
            'order' => 'asc',
            'per_page' => 10,
        ]);

        $view = app(AdminPostsController::class)->index($type, $request);

        $this->assertSame('cms::admin.posts.index', $view->name());
        $this->assertSame($type->id, $view->getData()['type']->id);
        $this->assertFalse($view->getData()['trashed']);
        $this->assertSame([$matching->id], $view->getData()['posts']->pluck('id')->all());
        $this->assertSame('Emergency', $view->getData()['search']);
    }

    #[Test]
    public function posts_create_edit_show_and_trash_return_expected_responses(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);

        $create = app(AdminPostsController::class)->create($type, Request::create('/admin/alerts/create'));
        $edit = app(AdminPostsController::class)->edit($type, $post);
        $show = app(AdminPostsController::class)->show($type, $post);

        $post->delete();
        $trash = app(AdminPostsController::class)->trash($type, Request::create('/admin/alerts/trash'));

        $this->assertSame('cms::admin.posts.create', $create->name());
        $this->assertSame('cms::admin.posts.edit', $edit->name());
        $this->assertSame(route('admin.posts.edit', [$type, $post]), $show->getTargetUrl());
        $this->assertSame('cms::admin.posts.index', $trash->name());
        $this->assertTrue($trash->getData()['trashed']);
        $this->assertSame([$post->id], $trash->getData()['posts']->pluck('id')->all());
    }

    #[Test]
    public function posts_store_persists_validated_payload_and_syncs_categories_for_json_requests(): void
    {
        $type = $this->createPostType('alerts');
        $categoryType = new \Javaabu\Cms\Models\CategoryType([
            'name' => 'Alert Categories',
            'singular_name' => 'Alert Category',
            'slug' => 'alert-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $type->categoryType()->associate($categoryType);
        $type->features = ['categories' => true];
        $type->save();
        $category = new \Javaabu\Cms\Models\Category([
            'name' => 'Weather',
            'slug' => 'weather',
        ]);
        $category->type_id = $categoryType->id;
        $category->lang = 'en';
        $category->save();

        $request = \Mockery::mock(PostsRequest::class)->makePartial();
        $request->shouldReceive('validated')->once()->andReturn([
            'title' => 'Stored Alert',
            'slug' => 'stored-alert',
            'content' => 'Stored content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);
        $request->shouldReceive('input')->with('action')->andReturn(null);
        $request->shouldReceive('input')->with('status')->andReturn(null);
        $request->shouldReceive('input')->with('slug')->andReturn('Stored Alert');
        $request->shouldReceive('input')->with('lang', \Mockery::any())->andReturn('dv');
        $request->shouldReceive('input')->with('never_expire')->andReturn(false);
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('input')->with('categories', [])->andReturn([$category->id]);
        $request->shouldReceive('has')->with('department')->andReturn(false);
        $request->shouldReceive('has')->with('sidebar_menu')->andReturn(false);
        $request->shouldReceive('has')->with('sync_categories')->andReturn(true);
        $request->shouldReceive('has')->with('sync_documents')->andReturn(false);
        $request->shouldReceive('has')->with('sync_image_gallery')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(true);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(AdminPostsController::class)->store($type, $request);

        $created = Post::query()->where('title', 'Stored Alert')->firstOrFail();
        $this->assertSame(200, $response->status());
        $this->assertSame('stored-alert', $created->slug);
        $this->assertSame('dv', $created->lang);
        $this->assertSame([$category->id], $created->categories()->pluck('categories.id')->all());
    }

    #[Test]
    public function posts_destroy_returns_json_true_on_success(): void
    {
        $postType = $this->createPostType('alerts');
        $post = $this->createPost($postType);

        $request = Request::create('/admin/posts/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminPostsController::class)->destroy($postType, $post, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    #[Test]
    public function post_binding_query_throws_when_post_does_not_belong_to_post_type(): void
    {
        $alertsType = $this->createPostType('alerts');
        $newsType = $this->createPostType('news');
        $newsPost = $this->createPost($newsType);

        $this->expectException(ModelNotFoundException::class);

        $this->resolvePostForTypeOrFail($alertsType, $newsPost->id);
    }

    #[Test]
    public function posts_bulk_rejects_post_ids_from_other_post_type(): void
    {
        $alertsType = $this->createPostType('alerts');
        $newsType = $this->createPostType('news');
        $newsPost = $this->createPost($newsType);

        $this->expectException(ValidationException::class);

        $request = Request::create('/admin/posts/bulk', 'PATCH', [
            'action' => 'delete',
            'posts' => [$newsPost->id],
        ]);

        app(AdminPostsController::class)->bulk($alertsType, $request);
    }

    #[Test]
    public function posts_bulk_throws_when_view_any_authorization_fails(): void
    {
        $type = $this->createPostType('alerts');

        $controller = \Mockery::mock(AdminPostsController::class)->makePartial();
        $controller->shouldReceive('authorize')->once()->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/posts/bulk', 'PATCH', [
            'action' => 'delete',
            'posts' => [],
        ]);

        $controller->bulk($type, $request);
    }

    #[Test]
    public function posts_force_delete_returns_json_true_on_success(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);
        $post->delete();

        $request = Request::create('/admin/posts/force-delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminPostsController::class)->forceDelete($type, $post->id, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function posts_restore_returns_json_true_on_success(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);
        $post->delete();

        $request = Request::create('/admin/posts/restore', 'PATCH', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminPostsController::class)->restore($type, $post->id, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    private function resolvePostForTypeOrFail(PostType $postType, int $postId): Post
    {
        return Post::query()->where('type', $postType->slug)->findOrFail($postId);
    }

    private function createPostType(string $slug): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
            'icon' => 'ri-file-line',
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
            'slug' => 'post-' . fake()->unique()->numberBetween(1, 999999),
            'content' => 'Default content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));

        $post->lang = $attributes['lang'] ?? 'en';
        $post->save();

        return $post;
    }
}
