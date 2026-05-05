<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function create_category_type(array $attributes = []): CategoryType
    {
        $category_type = new CategoryType(array_merge([
            'name' => 'Default Categories',
            'singular_name' => 'Default Category',
            'slug' => 'default-categories',
        ], $attributes));

        $category_type->lang = 'en';
        $category_type->save();

        return $category_type;
    }

    private function create_category(CategoryType $category_type, array $attributes = []): Category
    {
        $data = array_merge([
            'type_id' => $category_type->id,
            'name' => 'Default Category',
            'slug' => 'default-category',
        ], $attributes);

        $category = new Category([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        $category->type_id = $data['type_id'];

        $category->lang = 'en';
        $category->save();

        return $category;
    }

    #[Test]
    public function it_rejects_duplicate_slug_within_same_category_type(): void
    {
        $news = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $this->create_category($news, ['name' => 'Policy', 'slug' => 'policy']);

        $candidate = new Category([
            'name' => 'Another Policy',
            'slug' => 'another-policy',
        ]);
        $candidate->type_id = $news->id;

        $this->assertFalse($candidate->isUniqueSlug('policy'));
    }

    #[Test]
    public function it_allows_same_slug_across_different_category_types(): void
    {
        $news = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $events = $this->create_category_type([
            'name' => 'Event Categories',
            'singular_name' => 'Event Category',
            'slug' => 'event-categories',
        ]);

        $this->create_category($news, ['name' => 'Policy', 'slug' => 'policy']);

        $candidate = new Category([
            'name' => 'Event Policy',
            'slug' => 'event-policy',
        ]);
        $candidate->type_id = $events->id;

        $this->assertTrue($candidate->isUniqueSlug('policy'));
    }

    #[Test]
    public function it_allows_same_slug_for_same_category_instance_on_update(): void
    {
        $news = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $category = $this->create_category($news, ['name' => 'Policy', 'slug' => 'policy']);

        $this->assertTrue($category->isUniqueSlug('policy'));
    }

    #[Test]
    public function it_filters_categories_by_type_slug_using_category_type_scope(): void
    {
        $news = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $events = $this->create_category_type([
            'name' => 'Event Categories',
            'singular_name' => 'Event Category',
            'slug' => 'event-categories',
        ]);

        $news_category = $this->create_category($news, ['name' => 'Policy', 'slug' => 'policy']);
        $this->create_category($events, ['name' => 'Conference', 'slug' => 'conference']);

        $ids = Category::categoryType('news-categories')->pluck('id')->all();

        $this->assertSame([$news_category->id], $ids);
    }
}
