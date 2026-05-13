<?php

namespace Javaabu\Cms\Tests\Unit\PostTypes;

use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\PostTypes\PostType;
use Javaabu\Cms\PostTypes\PostTypes;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostTypeBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_post_type_configuration_through_fluent_api(): void
    {
        $postType = PostType::make('news')
            ->name('News')
            ->nameDv('ޚަބަރު')
            ->singularName('News Article')
            ->icon('zmdi-assignment')
            ->categoryType('news-categories')
            ->features(PostTypeFeatures::CATEGORIES, 'excerpt')
            ->description('Latest news')
            ->ogDescription('Open graph description')
            ->orderColumn(9);

        $this->assertSame([
            'slug' => 'news',
            'name' => 'News',
            'name_dv' => 'ޚަބަރު',
            'singular_name' => 'News Article',
            'icon' => 'zmdi-assignment',
            'category_type' => 'news-categories',
            'features' => [
                'categories' => true,
                'excerpt' => true,
            ],
            'description' => 'Latest news',
            'og_description' => 'Open graph description',
            'order_column' => 9,
        ], $postType->toArray());
    }

    #[Test]
    public function it_normalizes_mixed_array_and_fluent_post_type_configurations(): void
    {
        $normalized = PostTypes::normalize([
            PostType::make('news')
                ->name('News')
                ->features(PostTypeFeatures::CATEGORIES)
                ->orderColumn(3),
            'blog' => [
                'name' => 'Blog',
                'features' => [
                    'excerpt' => true,
                ],
            ],
        ]);

        $this->assertArrayHasKey('news', $normalized);
        $this->assertArrayHasKey('blog', $normalized);
        $this->assertSame(true, $normalized['news']['features']['categories']);
        $this->assertSame(3, $normalized['news']['order_column']);
        $this->assertSame('blog', $normalized['blog']['slug']);
    }

    #[Test]
    public function it_supports_both_variadic_and_array_feature_definitions(): void
    {
        $postType = PostType::make('blog')
            ->features(
                PostTypeFeatures::CATEGORIES,
                'excerpt',
                ['featured-image' => 'Hero Image']
            );

        $this->assertSame([
            'categories' => true,
            'excerpt' => true,
            'featured-image' => 'Hero Image',
        ], $postType->toArray()['features']);
    }
}
