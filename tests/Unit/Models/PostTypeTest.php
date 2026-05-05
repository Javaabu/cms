<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostTypeTest extends TestCase
{
    use RefreshDatabase;

    private function create_post_type(array $attributes = []): PostType
    {
        $post_type = new PostType(array_merge([
            'name' => 'Default Type',
            'singular_name' => 'Default Item',
            'slug' => 'default-type',
            'icon' => 'ri-file-line',
        ], $attributes));

        $post_type->lang = 'en';
        $post_type->save();

        return $post_type;
    }

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
    public function it_builds_permission_slug_in_snake_case(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'Press Releases',
            'icon' => 'ri-megaphone-line',
        ]);

        $this->assertSame('press_releases', $post_type->permission_slug);
    }

    #[Test]
    public function it_slugifies_slug_when_creating_post_type(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'Press Releases',
            'icon' => 'ri-megaphone-line',
        ]);

        $this->assertSame('press-releases', $post_type->slug);
    }

    #[Test]
    public function it_requires_unique_slug_at_database_level(): void
    {
        $this->expectException(QueryException::class);

        $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $duplicate = new PostType([
            'name' => 'Duplicate News',
            'singular_name' => 'Duplicate News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);

        $duplicate->lang = 'en';
        $duplicate->save();
    }

    #[Test]
    public function it_detects_features_by_string_and_enum(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Publications',
            'singular_name' => 'Publication',
            'slug' => 'publications',
            'icon' => 'ri-book-open-line',
            'features' => [
                PostTypeFeatures::DOCUMENTS->value => true,
                PostTypeFeatures::IMAGE_GALLERY->value => 'featured gallery',
            ],
        ]);

        $this->assertTrue($post_type->hasFeature(PostTypeFeatures::DOCUMENTS));
        $this->assertTrue($post_type->hasFeature(PostTypeFeatures::IMAGE_GALLERY->value));
        $this->assertFalse($post_type->hasFeature(PostTypeFeatures::VIDEO_LINK));
    }

    #[Test]
    public function it_returns_feature_names_for_boolean_and_string_values(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Media',
            'singular_name' => 'Media Item',
            'slug' => 'media',
            'icon' => 'ri-image-line',
            'features' => [
                PostTypeFeatures::DOCUMENTS->value => true,
                PostTypeFeatures::IMAGE_GALLERY->value => 'homepage gallery',
            ],
        ]);

        $this->assertSame('Documents', $post_type->getFeatureName(PostTypeFeatures::DOCUMENTS));
        $this->assertSame('Homepage Gallery', $post_type->getFeatureName(PostTypeFeatures::IMAGE_GALLERY));
    }

    #[Test]
    public function it_returns_null_for_missing_feature_details(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Pages',
            'singular_name' => 'Page',
            'slug' => 'pages',
            'icon' => 'ri-file-list-line',
            'features' => [
                PostTypeFeatures::DOCUMENTS->value => true,
            ],
        ]);

        $this->assertNull($post_type->getFeatureName(PostTypeFeatures::VIDEO_LINK));
        $this->assertNull($post_type->getFeatureCollectionName(PostTypeFeatures::VIDEO_LINK));
    }

    #[Test]
    public function it_returns_feature_collection_name_for_supported_features(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Galleries',
            'singular_name' => 'Gallery',
            'slug' => 'galleries',
            'icon' => 'ri-gallery-line',
            'features' => [
                PostTypeFeatures::IMAGE_GALLERY->value => true,
            ],
        ]);

        $this->assertSame('image_gallery', $post_type->getFeatureCollectionName(PostTypeFeatures::IMAGE_GALLERY));
    }

    #[Test]
    public function it_returns_empty_categories_when_category_type_is_not_set(): void
    {
        $post_type = $this->create_post_type([
            'name' => 'Pages',
            'singular_name' => 'Page',
            'slug' => 'pages',
            'icon' => 'ri-file-list-line',
            'category_type_id' => null,
        ]);

        $this->assertCount(0, $post_type->categoriesFor());
    }

    #[Test]
    public function it_returns_categories_for_its_assigned_category_type_only(): void
    {
        $news_categories = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $events_categories = $this->create_category_type([
            'name' => 'Event Categories',
            'singular_name' => 'Event Category',
            'slug' => 'event-categories',
        ]);

        $post_type = $this->create_post_type([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
            'category_type_id' => $news_categories->id,
        ]);

        $first = $this->create_category($news_categories, ['name' => 'Politics', 'slug' => 'politics']);
        $second = $this->create_category($news_categories, ['name' => 'Business', 'slug' => 'business']);
        $this->create_category($events_categories, ['name' => 'Conference', 'slug' => 'conference']);

        $ids = $post_type->categoriesFor()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $ids);
    }
}
