<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_find_categories_using_search(): void
    {
        $category_type = $this->create_category_type('topics');

        $matching_category = $this->create_category($category_type, 'Policy Updates');
        $this->create_category($category_type, 'Sports News');

        $results = Category::query()->search('Policy')->pluck('id')->all();

        $this->assertSame([$matching_category->id], $results);
    }

    #[Test]
    public function it_can_detect_unique_slug_per_category_type(): void
    {
        $first_type = $this->create_category_type('topics');
        $second_type = $this->create_category_type('departments');

        $this->create_category($first_type, 'Shared Slug', 'shared-slug');

        $category_in_same_type = new Category([
            'name' => 'Another One',
            'slug' => 'another-one',
        ]);
        $category_in_same_type->type_id = $first_type->id;
        $category_in_same_type->lang = 'en';

        $category_in_other_type = new Category([
            'name' => 'Different Type',
            'slug' => 'different-type',
        ]);
        $category_in_other_type->type_id = $second_type->id;
        $category_in_other_type->lang = 'en';

        $this->assertFalse($category_in_same_type->isUniqueSlug('shared-slug'));
        $this->assertTrue($category_in_other_type->isUniqueSlug('shared-slug'));
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

    private function create_category(CategoryType $category_type, string $name, ?string $slug = null): Category
    {
        $category = new Category([
            'name' => $name,
            'slug' => $slug ?: str($name)->slug(),
        ]);

        $category->type_id = $category_type->id;
        $category->lang = 'en';
        $category->save();

        return $category;
    }
}
