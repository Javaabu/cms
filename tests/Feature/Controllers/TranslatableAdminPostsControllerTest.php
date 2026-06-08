<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\TranslatablePost;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Translatable\Http\Controllers\Admin\PostsController as TranslatableAdminPostsController;
use PHPUnit\Framework\Attributes\Test;

class TranslatableAdminPostsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Gate::shouldReceive('authorize')->andReturn(true);

        Route::get('/_test/admin/{language}/{post_type}', [TranslatableAdminPostsController::class, 'index'])->name('admin.posts.index');
        Route::get('/_test/admin/{language}/{post_type}/create', [TranslatableAdminPostsController::class, 'create'])->name('admin.posts.create');
        Route::get('/_test/admin/{language}/{post_type}/{post}/edit', [TranslatableAdminPostsController::class, 'edit'])->name('admin.posts.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function create_show_and_edit_return_expected_responses(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createTranslatablePost($type);

        $create = app(TranslatableAdminPostsController::class)->create('en', $type, Request::create('/admin/translatable/posts/create', 'GET'));
        $show = app(TranslatableAdminPostsController::class)->show('en', $type, $post);
        $edit = app(TranslatableAdminPostsController::class)->edit('en', $type, $post);

        $this->assertSame('cms::admin.posts.create', $create->name());
        $this->assertSame(action([TranslatableAdminPostsController::class, 'edit'], ['en', $type, $post]), $show->getTargetUrl());
        $this->assertSame('cms::admin.posts.edit', $edit->name());
        $this->assertTrue($edit->getData()['post']->is($post));
    }

    #[Test]
    public function index_and_trash_return_expected_views_for_translatable_posts(): void
    {
        config()->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);
        config()->set('cms.models.post', TranslatablePost::class);

        $type = $this->createPostType('alerts');
        $published = $this->createTranslatablePost($type, [
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $trashed = $this->createTranslatablePost($type, [
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $trashed->delete();

        $controller = \Mockery::mock(TranslatableAdminPostsController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('getOrderBy')->andReturn('created_at');
        $controller->shouldReceive('getOrder')->andReturn('asc');
        $controller->shouldReceive('getPerPage')->andReturn(10);

        $index = $controller->index('en', $type, Request::create('/admin/translatable/posts', 'GET'));
        $trash = $controller->trash('en', $type, Request::create('/admin/translatable/posts/trash', 'GET'));

        $this->assertSame('cms::admin.posts.index', $index->name());
        $this->assertSame([$published->id], $index->getData()['posts']->pluck('id')->all());
        $this->assertFalse($index->getData()['trashed']);
        $this->assertSame('cms::admin.posts.index', $trash->name());
        $this->assertTrue($trash->getData()['trashed']);
        $this->assertSame([$trashed->id], $trash->getData()['posts']->pluck('id')->all());
    }

    #[Test]
    public function destroy_returns_json_true_on_success(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);

        $request = Request::create('/admin/translatable/posts/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(TranslatableAdminPostsController::class)->destroy('en', $type, $post, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    #[Test]
    public function bulk_rejects_post_ids_from_other_post_type(): void
    {
        $alertsType = $this->createPostType('alerts');
        $newsType = $this->createPostType('news');
        $newsPost = $this->createPost($newsType);

        $this->expectException(ValidationException::class);

        $request = Request::create('/admin/translatable/posts/bulk', 'PATCH', [
            'action' => 'delete',
            'posts' => [$newsPost->id],
        ]);

        app(TranslatableAdminPostsController::class)->bulk('en', $alertsType, $request);
    }

    #[Test]
    public function bulk_throws_when_view_any_authorization_fails(): void
    {
        $type = $this->createPostType('alerts');
        $controller = \Mockery::mock(TranslatableAdminPostsController::class)->makePartial();
        $controller->shouldReceive('authorize')->once()->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/translatable/posts/bulk', 'PATCH', [
            'action' => 'delete',
            'posts' => [],
        ]);

        $controller->bulk('en', $type, $request);
    }

    #[Test]
    public function force_delete_and_restore_return_json_true_on_success(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);
        $post->delete();

        $deleteRequest = Request::create('/admin/translatable/posts/force-delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $deleteResponse = app(TranslatableAdminPostsController::class)->forceDelete('en', $type, $post->id, $deleteRequest);

        $this->assertSame(200, $deleteResponse->status());
        $this->assertSame('true', $deleteResponse->getContent());
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);

        $restorable = $this->createPost($type);
        $restorable->delete();
        $restoreRequest = Request::create('/admin/translatable/posts/restore', 'PATCH', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $restoreResponse = app(TranslatableAdminPostsController::class)->restore('en', $type, $restorable->id, $restoreRequest);

        $this->assertSame(200, $restoreResponse->status());
        $this->assertSame('true', $restoreResponse->getContent());
        $this->assertDatabaseHas('posts', ['id' => $restorable->id, 'deleted_at' => null]);
    }

    #[Test]
    public function restore_redirects_to_index_on_browser_request(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createPost($type);
        $post->delete();

        $restoreRequest = Request::create('/admin/translatable/posts/restore', 'PATCH');
        $restoreResponse = app(TranslatableAdminPostsController::class)->restore('en', $type, $post->id, $restoreRequest);

        $this->assertSame(302, $restoreResponse->status());
        $this->assertSame(
            action([TranslatableAdminPostsController::class, 'index'], ['language' => 'en', 'post_type' => $type]),
            $restoreResponse->getTargetUrl()
        );
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    #[Test]
    public function store_persists_payload_and_redirects_to_edit(): void
    {
        $type = $this->createPostType('alerts');
        config()->set('cms.models.department', PostType::class);

        $request = \Mockery::mock(PostRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'title' => 'Stored Post',
            'slug' => 'stored-post',
            'content' => 'Stored content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);
        $request->shouldReceive('input')->with('action')->andReturn(null);
        $request->shouldReceive('input')->with('status')->andReturn(null);
        $request->shouldReceive('input')->with('slug')->andReturn('stored-post');
        $request->shouldReceive('input')->with('lang', \Mockery::any())->andReturn('dv');
        $request->shouldReceive('input')->with('department')->andReturn(null);
        $request->shouldReceive('input')->with('never_expire')->andReturn(false);
        $request->shouldReceive('input')->with('component')->andReturn(null);
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('has')->with('sync_categories')->andReturn(false);
        $request->shouldReceive('has')->with('sidebar_menu')->andReturn(false);
        $request->shouldReceive('has')->with('sync_documents')->andReturn(false);
        $request->shouldReceive('has')->with('sync_image_gallery')->andReturn(false);
        $request->shouldReceive('hasAny')->with(['lat', 'lng'])->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(TranslatableAdminPostsController::class)->store('en', $type, $request);

        $created = Post::query()->where('slug', 'stored-post')->firstOrFail();
        $this->assertSame('dv', $created->lang);
        $this->assertSame(action([TranslatableAdminPostsController::class, 'edit'], ['en', $type, $created]), $response->getTargetUrl());
    }

    #[Test]
    public function update_changes_slug_language_and_redirects_to_edit(): void
    {
        $type = $this->createPostType('alerts');
        $post = $this->createTranslatablePost($type);
        config()->set('cms.models.department', PostType::class);

        $request = \Mockery::mock(PostRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'title' => 'Updated Post',
            'slug' => 'updated-post',
            'content' => 'Updated content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);
        $request->shouldReceive('input')->with('is_translation')->andReturn(false);
        $request->shouldReceive('input')->with('lang')->andReturn('dv');
        $request->shouldReceive('input')->with('action')->andReturn(null);
        $request->shouldReceive('input')->with('status')->andReturn(null);
        $request->shouldReceive('input')->with('slug')->andReturn('updated-post');
        $request->shouldReceive('input')->with('hide_translation', false)->andReturn(true);
        $request->shouldReceive('input')->with('recently_updated', false)->andReturn(true);
        $request->shouldReceive('input')->with('never_expire')->andReturn(false);
        $request->shouldReceive('input')->with('component')->andReturn(null);
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('has')->with('department')->andReturn(false);
        $request->shouldReceive('has')->with('sync_tags')->andReturn(false);
        $request->shouldReceive('has')->with('sync_categories')->andReturn(false);
        $request->shouldReceive('has')->with('sync_documents')->andReturn(false);
        $request->shouldReceive('has')->with('sync_related_galleries')->andReturn(false);
        $request->shouldReceive('has')->with('sync_image_gallery')->andReturn(false);
        $request->shouldReceive('hasAny')->with(['lat', 'lng'])->andReturn(false);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(TranslatableAdminPostsController::class)->update('en', $request, $type, $post);

        $post->refresh();

        $this->assertSame('updated-post', $post->slug);
        $this->assertSame('dv', $post->lang->value ?? $post->lang);
        $this->assertSame(action([TranslatableAdminPostsController::class, 'edit'], ['en', $type, $post]), $response->getTargetUrl());
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

    private function createPost(PostType $postType): Post
    {
        $post = new Post([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'post-' . fake()->unique()->numberBetween(1, 999999),
            'content' => 'Default content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);
        $post->lang = 'en';
        $post->save();
        return $post;
    }

    private function createTranslatablePost(PostType $postType, array $attributes = []): TranslatablePost
    {
        $post = new TranslatablePost(array_merge([
            'type' => $postType->slug,
            'title' => 'Translatable Post',
            'slug' => 'translatable-post-' . fake()->unique()->numberBetween(1, 999999),
            'content' => 'Translatable content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));
        $post->lang = $attributes['lang'] ?? \Javaabu\Cms\Enums\Languages::EN;
        $post->save();

        return $post;
    }
}
