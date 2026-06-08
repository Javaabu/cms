<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
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

    #[Test]
    public function it_builds_category_lists_and_omits_descendants_of_the_skipped_category(): void
    {
        $type = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);

        $root = $this->create_category($type, ['name' => 'Root', 'slug' => 'root']);
        $child = $this->create_category($type, ['name' => 'Child', 'slug' => 'child']);
        $child->appendToNode($root)->save();
        $grandchild = $this->create_category($type, ['name' => 'Grandchild', 'slug' => 'grandchild']);
        $grandchild->appendToNode($child)->save();
        $sibling = $this->create_category($type, ['name' => 'Sibling', 'slug' => 'sibling']);

        $all = Category::categoryList($type->id);
        $skipped = Category::categoryList($type->id, $child);

        $this->assertArrayHasKey($root->id, $all);
        $this->assertArrayHasKey($child->id, $all);
        $this->assertArrayHasKey($grandchild->id, $all);
        $this->assertArrayHasKey($sibling->id, $all);
        $this->assertArrayNotHasKey($child->id, $skipped);
        $this->assertArrayNotHasKey($grandchild->id, $skipped);
        $this->assertArrayHasKey($root->id, $skipped);
        $this->assertArrayHasKey($sibling->id, $skipped);
    }

    #[Test]
    public function it_builds_admin_and_public_urls_and_tree_helpers(): void
    {
        Route::get('/admin/category-types/{category_type}/{category}/edit', fn () => 'edit')->name('admin.categories.edit');
        Route::get('/admin/posts/{post_type}', fn () => 'posts')->name('admin.posts.index');
        Route::get('/admin/staff', fn () => 'staff')->name('admin.staff.index');
        Route::get('/news', fn () => 'news')->name('web.posts.index.news');
        Route::get('/staff-directory', fn () => 'staff')->name('web.staff-directory.index');
        Route::get('/departments', fn () => 'departments')->name('web.departments.index');
        Route::getRoutes()->refreshNameLookups();

        config()->set('cms.should_translate', false);
        app()->setLocale('en');

        $newsType = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $postType = new PostType([
            'name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'icon' => 'ri-news-line',
        ]);
        $postType->categoryType()->associate($newsType);
        $postType->lang = 'en';
        $postType->save();

        $parent = $this->create_category($newsType, ['name' => 'Parent', 'slug' => 'parent']);
        $child = $this->create_category($newsType, ['name' => 'Child', 'slug' => 'child']);
        $child->appendToNode($parent)->save();
        $child->refresh();

        $this->assertSame(route('admin.categories.edit', [$newsType, $child]), $child->admin_url);
        $this->assertSame(url('/news?category=' . $child->id), $child->permalink);
        $this->assertTrue($child->has_parent);
        $this->assertFalse($child->has_children);
        $this->assertTrue($parent->fresh()->has_children);
        $this->assertStringEndsWith('Child', $child->depth_name);
        $this->assertSame($child->depth_name, $child->admin_link_name);
        $this->assertSame(route('admin.posts.index', $postType), $child->post_admin_link);

        $child->order_column = null;
        $this->assertSame(0, $child->order_column);
    }

    #[Test]
    public function it_builds_localized_category_urls_when_the_route_expects_a_locale_parameter(): void
    {
        Route::get('/admin/category-types/{language}/{category_type}/{category}/edit', fn () => 'edit')->name('admin.categories.edit');
        Route::getRoutes()->refreshNameLookups();

        app()->setLocale('en');

        $type = $this->create_category_type([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $category = $this->create_category($type, ['name' => 'Policy', 'slug' => 'policy']);

        $this->assertSame(url('/admin/category-types/en/news-categories/' . $category->id . '/edit'), $category->url('edit', 'en'));
    }
}
