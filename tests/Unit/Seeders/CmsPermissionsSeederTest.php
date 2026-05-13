<?php

namespace Javaabu\Cms\Tests\Unit\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\seeders\CmsPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;

class CmsPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_post_type_and_category_type_permission_maps(): void
    {
        $postType = new PostType([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'press-releases',
            'icon' => 'ri-newspaper-line',
        ]);
        $postType->lang = 'en';
        $postType->save();

        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $permissions = (new CmsPermissionsSeeder())->getPermissions();

        $this->assertArrayHasKey('press_releases', $permissions);
        $this->assertArrayHasKey('news_categories', $permissions);
        $this->assertArrayHasKey('edit_press_releases', $permissions['press_releases']);
        $this->assertArrayHasKey('publish_others_press_releases', $permissions['press_releases']);
        $this->assertArrayHasKey('delete_news_categories', $permissions['news_categories']);
        $this->assertStringStartsWith('Edit own', $permissions['press_releases']['edit_press_releases']);
    }

    #[Test]
    public function it_exposes_default_media_permissions(): void
    {
        $method = new \ReflectionMethod(CmsPermissionsSeeder::class, 'getMediaPermissions');
        $method->setAccessible(true);
        $permissions = $method->invoke(null);

        $this->assertArrayHasKey('view_media', $permissions);
        $this->assertArrayHasKey('delete_other_users_media', $permissions);
        $this->assertSame('Delete all media', $permissions['delete_other_users_media']);
    }
}
