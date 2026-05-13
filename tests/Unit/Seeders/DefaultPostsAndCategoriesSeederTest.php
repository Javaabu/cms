<?php

namespace Javaabu\Cms\Tests\Unit\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Seeders\DefaultPostsAndCategoriesSeeder;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DefaultPostsAndCategoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seed_defaults_creates_expected_records_and_is_idempotent(): void
    {
        $addDescription = ! Schema::hasColumn('post_types', 'description');
        $addOgDescription = ! Schema::hasColumn('post_types', 'og_description');

        if ($addDescription || $addOgDescription) {
            Schema::table('post_types', function ($table): void {
                if (! Schema::hasColumn('post_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('post_types', 'og_description')) {
                    $table->text('og_description')->nullable();
                }
            });
        }

        config()->set('cms.default_category_types', [
            'news-categories' => [
                'name' => 'News Categories',
                'singular_name' => 'News Category',
            ],
        ]);

        config()->set('cms.default_post_types', [
            'news' => [
                'name' => 'News',
                'singular_name' => 'News Item',
                'icon' => 'ri-newspaper-line',
                'category_type' => 'news-categories',
                'features' => ['documents' => true],
                'order_column' => 7,
            ],
        ]);

        config()->set('cms.default_categories', [
            'news-categories' => [
                [
                    'name' => 'Announcements',
                    'slug' => 'announcements',
                    'icon' => 'ri-megaphone-line',
                    'order_column' => 11,
                ],
            ],
        ]);

        DefaultPostsAndCategoriesSeeder::seedDefaults();
        DefaultPostsAndCategoriesSeeder::seedDefaults();

        $this->assertSame(1, CategoryType::query()->where('slug', 'news-categories')->count());
        $this->assertSame(1, PostType::query()->where('slug', 'news')->count());
        $this->assertSame(1, Category::query()->where('slug', 'announcements')->count());

        $postType = PostType::query()->where('slug', 'news')->firstOrFail();
        $categoryType = CategoryType::query()->where('slug', 'news-categories')->firstOrFail();
        $category = Category::query()->where('slug', 'announcements')->firstOrFail();

        $this->assertSame($categoryType->id, $postType->category_type_id);
        $this->assertSame('ri-newspaper-line', $postType->icon);
        $this->assertSame('ri-megaphone-line', $category->icon);
        $this->assertSame(11, $category->order_column);
    }
}
