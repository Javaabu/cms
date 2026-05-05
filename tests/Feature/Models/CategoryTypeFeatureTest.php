<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryTypeFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_auto_generates_slug_when_name_is_set(): void
    {
        $category_type = new CategoryType([
            'name' => 'Staff Categories',
            'singular_name' => 'Staff Category',
        ]);

        $category_type->lang = 'en';
        $category_type->save();

        $this->assertSame('staff-categories', $category_type->slug);
    }

    #[Test]
    public function it_can_list_categories_from_relationship(): void
    {
        $category_type = $this->create_category_type('topics');

        $category = new Category([
            'name' => 'Policy',
            'slug' => 'policy',
        ]);

        $category->type_id = $category_type->id;
        $category->lang = 'en';
        $category->save();

        $category_ids = $category_type->categories()->pluck('id')->all();

        $this->assertSame([$category->id], $category_ids);
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
}
