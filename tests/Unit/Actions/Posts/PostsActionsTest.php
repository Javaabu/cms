<?php

namespace Javaabu\Cms\Tests\Unit\Actions\Posts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Javaabu\Cms\Actions\Posts\CreatePostAction;
use Javaabu\Cms\Actions\Posts\EditPostAction;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class PostsActionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function create_post_action_persists_a_new_post_using_validated_payload(): void
    {
        $postType = $this->createPostType('news');

        $request = Mockery::mock(PostRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'type' => $postType->slug,
                'title' => 'Mission Critical Post',
                'slug' => 'mission-critical-post',
                'status' => PostStatus::DRAFT->value,
                'published_at' => '2026-05-01 10:00:00',
                'lang' => 'en',
            ]);

        $post = (new CreatePostAction())->handle($request);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertNotNull($post->id);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'type' => $postType->slug,
            'title' => 'Mission Critical Post',
            'slug' => 'mission-critical-post',
            'status' => PostStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function edit_post_action_updates_the_post_with_validated_payload(): void
    {
        $postType = $this->createPostType('pages');
        $post = $this->createPost($postType, [
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);

        $request = Mockery::mock(PostRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'title' => 'Updated Title',
                'slug' => 'updated-title',
            ]);
        $request->shouldReceive('hasFile')
            ->once()
            ->with('media')
            ->andReturnFalse();

        $result = (new EditPostAction())->handle($post, $request);

        $this->assertTrue($result->is($post));
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'slug' => 'updated-title',
        ]);
    }

    #[Test]
    public function edit_post_action_attaches_media_when_media_file_is_present(): void
    {
        $post = Mockery::mock(Post::class);
        $request = Mockery::mock(PostRequest::class);
        $file = UploadedFile::fake()->image('hero.jpg');
        $pendingMedia = Mockery::mock();

        $request->shouldReceive('validated')
            ->once()
            ->andReturn(['title' => 'Updated']);
        $post->shouldReceive('update')
            ->once()
            ->with(['title' => 'Updated']);
        $request->shouldReceive('hasFile')
            ->once()
            ->with('media')
            ->andReturnTrue();
        $request->shouldReceive('file')
            ->once()
            ->with('media')
            ->andReturn($file);
        $post->shouldReceive('addMedia')
            ->once()
            ->with($file)
            ->andReturn($pendingMedia);
        $pendingMedia->shouldReceive('toMediaCollection')
            ->once()
            ->with('media');

        $result = (new EditPostAction())->handle($post, $request);

        $this->assertSame($post, $result);
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
            'slug' => 'default-post',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));

        $post->lang = 'en';
        $post->save();

        return $post;
    }
}
