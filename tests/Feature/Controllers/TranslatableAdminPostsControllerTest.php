<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
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

        \Illuminate\Support\Facades\Route::get('/_test/admin/{language}/{post_type}', [TranslatableAdminPostsController::class, 'index'])
            ->name('admin.posts.index');
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();

        $restoreRequest = Request::create('/admin/translatable/posts/restore', 'PATCH');
        $restoreResponse = app(TranslatableAdminPostsController::class)->restore('en', $type, $post->id, $restoreRequest);

        $this->assertSame(302, $restoreResponse->status());
        $this->assertSame(
            action([TranslatableAdminPostsController::class, 'index'], ['language' => 'en', 'post_type' => $type]),
            $restoreResponse->getTargetUrl()
        );
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
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
}
