<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Http\Controllers\Admin\PostsController as AdminPostsController;
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
