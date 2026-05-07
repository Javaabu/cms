<?php

namespace Javaabu\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Cms;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\PostTypes\PostType as FluentPostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_post_types_with_default_configuration(): void
    {
        (new Cms())->registerPostTypes([
            'pages' => [
                'name' => 'Pages',
                'name_singular' => 'Page',
                'icon' => 'ri-file-line',
                'features' => ['page-style' => true],
            ],
        ]);

        $postType = PostType::whereSlug('pages')->firstOrFail();

        $this->assertSame('Pages', $postType->name);
        $this->assertSame('Page', $postType->singular_name);
        $this->assertSame('en', $postType->lang);
        $this->assertSame('en', $postType->categoryType->lang);
        $this->assertSame(['page-style' => true], $postType->features);
    }

    #[Test]
    public function it_registers_post_types_from_fluent_configuration_and_keeps_registration_idempotent(): void
    {
        config()->set('cms.should_translate', true);

        $cms = new Cms();

        $cms->registerPostTypes([
            FluentPostType::make('news')
                ->name('News')
                ->singularName('News Article')
                ->icon('ri-news-line')
                ->features([PostTypeFeatures::CATEGORIES->value, 'excerpt']),
            'pages' => [
                'name' => 'Pages',
                'name_singular' => 'Page',
                'icon' => 'ri-file-line',
                'features' => ['page-style' => true],
            ],
        ]);

        $this->assertDatabaseCount('post_types', 2);
        $this->assertDatabaseCount('category_types', 2);

        $news = PostType::whereSlug('news')->firstOrFail();
        $this->assertSame('News', $news->name);
        $this->assertSame('News Article', $news->singular_name);
        $this->assertSame('ri-news-line', $news->icon);
        $this->assertSame([
            'categories' => true,
            'excerpt' => true,
        ], $news->features);
        $this->assertSame(0, $news->order_column);
        $this->assertSame('news-categories', $news->categoryType->slug);

        $pages = PostType::whereSlug('pages')->firstOrFail();
        $this->assertSame(1, $pages->order_column);
        $this->assertSame(['page-style' => true], $pages->features);

        $cms->registerPostTypes([
            'news' => [
                'name' => 'Latest News',
                'name_singular' => 'Latest News Item',
                'icon' => 'ri-megaphone-line',
                'features' => ['documents' => true],
            ],
        ]);

        $news->refresh();

        $this->assertDatabaseCount('post_types', 2);
        $this->assertDatabaseCount('category_types', 2);
        $this->assertSame('Latest News', $news->name);
        $this->assertSame('Latest News Item', $news->singular_name);
        $this->assertSame('ri-megaphone-line', $news->icon);
        $this->assertSame(['documents' => true], $news->features);
    }

    #[Test]
    public function it_sets_default_language_when_registering_post_types_with_translation_enabled(): void
    {
        config()->set('cms.should_translate', true);
        config()->set('cms.default_language', 'dv');

        (new Cms())->registerPostTypes([
            'announcements' => [
                'name' => 'Announcements',
                'name_singular' => 'Announcement',
                'icon' => 'ri-notification-line',
                'features' => [],
            ],
        ]);

        $this->assertSame('dv', PostType::whereSlug('announcements')->value('lang'));
    }

    #[Test]
    public function it_seeds_post_type_permissions_without_overwriting_existing_entries(): void
    {
        $postType = new PostType([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'press-releases',
            'icon' => 'ri-newspaper-line',
            'features' => [],
        ]);
        $postType->lang = 'en';
        $postType->save();

        $permissions = (new Cms())->seedPostTypePermissions([
            $postType->permission_slug => [
                'custom_press_releases' => 'Custom permission',
            ],
            'unrelated' => [
                'view_unrelated' => 'View unrelated',
            ],
        ]);

        $this->assertSame('Custom permission', $permissions['press_releases']['custom_press_releases']);
        $this->assertSame('Edit own press releases', $permissions['press_releases']['edit_press_releases']);
        $this->assertSame('Publish all press releases', $permissions['press_releases']['publish_others_press_releases']);
        $this->assertSame(['view_unrelated' => 'View unrelated'], $permissions['unrelated']);
    }

    #[Test]
    public function it_seeds_category_type_permissions_without_overwriting_existing_entries(): void
    {
        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $permissions = (new Cms())->seedCategoryTypePermissions([
            $categoryType->permission_slug => [
                'custom_news_categories' => 'Custom permission',
            ],
        ]);

        $this->assertSame('Custom permission', $permissions['news_categories']['custom_news_categories']);
        $this->assertSame('Edit news categories', $permissions['news_categories']['edit_news_categories']);
        $this->assertSame('Delete news categories', $permissions['news_categories']['delete_news_categories']);
        $this->assertSame('View news categories', $permissions['news_categories']['view_news_categories']);
    }
}
