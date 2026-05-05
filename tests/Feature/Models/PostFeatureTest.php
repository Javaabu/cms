<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_find_post_using_search(): void
    {
        $post_type = $this->create_post_type('news');

        $matching_post = $this->create_post($post_type, [
            'title' => 'Budget Announcement',
            'content' => 'Annual budget details',
        ]);

        $this->create_post($post_type, [
            'title' => 'Sports Update',
            'content' => 'Match day highlights',
        ]);

        $results = Post::query()->search('budget')->pluck('id')->all();

        $this->assertSame([$matching_post->id], $results);
    }

    #[Test]
    public function it_can_filter_only_published_posts(): void
    {
        $post_type = $this->create_post_type('updates');

        $published_post = $this->create_post($post_type, [
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $this->create_post($post_type, [
            'status' => PostStatus::SCHEDULED->value,
            'published_at' => now()->addDay(),
        ]);

        $results = Post::query()->published()->pluck('id')->all();

        $this->assertSame([$published_post->id], $results);
    }

    #[Test]
    public function it_can_filter_posts_by_post_type_scope(): void
    {
        $news_type = $this->create_post_type('news');
        $blog_type = $this->create_post_type('blogs');

        $news_post = $this->create_post($news_type, ['title' => 'News One']);
        $this->create_post($blog_type, ['title' => 'Blog One']);

        $results = Post::query()->postType($news_type)->pluck('id')->all();

        $this->assertSame([$news_post->id], $results);
    }

    private function create_post_type(string $slug): PostType
    {
        $post_type = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
            'icon' => 'ri-file-line',
        ]);

        $post_type->lang = 'en';
        $post_type->save();

        return $post_type;
    }

    private function create_post(PostType $post_type, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $post_type->slug,
            'title' => 'Default Post',
            'slug' => 'default-post-' . fake()->unique()->numberBetween(1, 9999),
            'content' => 'Default content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));

        $post->lang = 'en';
        $post->save();

        return $post;
    }
}
