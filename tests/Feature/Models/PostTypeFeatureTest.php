<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostTypeFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_resolve_posts_relationship_from_post_type(): void
    {
        $post_type = $this->create_post_type('news');

        $post = $this->create_post($post_type);

        $related_post_ids = $post_type->posts()->pluck('id')->all();

        $this->assertSame([$post->id], $related_post_ids);
    }

    #[Test]
    public function it_can_return_categories_for_configured_category_type(): void
    {
        $category_type = $this->create_category_type('topics');

        $post_type = $this->create_post_type('articles', [
            'category_type_id' => $category_type->id,
        ]);

        $category = $this->create_category($category_type, 'Policy');

        $categories = $post_type->categoriesFor();

        $this->assertCount(1, $categories);
        $this->assertSame($category->id, $categories->first()->id);
    }

    private function create_post_type(string $slug, array $attributes = []): PostType
    {
        $post_type = new PostType(array_merge([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
            'icon' => 'ri-file-line',
        ], $attributes));

        $post_type->lang = 'en';
        $post_type->save();

        return $post_type;
    }

    private function create_post(PostType $post_type): Post
    {
        $post = new Post([
            'type' => $post_type->slug,
            'title' => 'Relationship Post',
            'slug' => 'relationship-post',
            'content' => 'Body',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ]);

        $post->lang = 'en';
        $post->save();

        return $post;
    }

    private function create_category_type(string $slug): CategoryType
    {
        $category_type = new CategoryType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
        ]);

        $category_type->lang = 'en';
        $category_type->save();

        return $category_type;
    }

    private function create_category(CategoryType $category_type, string $name): Category
    {
        $category = new Category([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);

        $category->type_id = $category_type->id;
        $category->lang = 'en';
        $category->save();

        return $category;
    }
}
