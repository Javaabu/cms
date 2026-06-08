<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Http\Controllers\Admin\CategoriesController as AdminCategoriesController;
use Javaabu\Cms\Http\Requests\CategoriesRequest;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminCategoriesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Gate::shouldReceive('authorize')->andReturn(true);

        Route::get('/_test/admin/category-types/{category_type}', fn () => 'index')->name('admin.categories.index');
        Route::get('/_test/admin/category-types/{category_type}/create', fn () => 'create')->name('admin.categories.create');
        Route::get('/_test/admin/category-types/{category_type}/{category}/edit', fn () => 'edit')->name('admin.categories.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function categories_index_create_show_and_edit_return_expected_responses(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $first = $this->createCategory($type, ['name' => 'Alpha', 'slug' => 'alpha']);
        $second = $this->createCategory($type, ['name' => 'Beta', 'slug' => 'beta']);

        $controller = \Mockery::mock(AdminCategoriesController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('getOrderBy')->andReturn('created_at');
        $controller->shouldReceive('getOrder')->andReturn('asc');
        $controller->shouldReceive('getPerPage')->andReturn(10);

        $index = $controller->index($type, Request::create('/admin/category-types/blog-categories', 'GET', [
            'per_page' => 10,
        ]));
        $create = $controller->create($type, Request::create('/admin/category-types/blog-categories/create', 'GET'));
        $show = $controller->show($type, $first);
        $edit = $controller->edit($type, $second);

        $this->assertSame('cms::admin.categories.index', $index->name());
        $this->assertSame([$first->id, $second->id], $index->getData()['categories']->pluck('id')->all());
        $this->assertSame('cms::admin.categories.create', $create->name());
        $this->assertSame(route('admin.categories.edit', [$type, $first]), $show->getTargetUrl());
        $this->assertSame('cms::admin.categories.edit', $edit->name());
        $this->assertTrue($edit->getData()['category']->is($second));
        $this->assertIsArray($edit->getData()['allowed_categories']);
    }

    #[Test]
    public function categories_destroy_returns_json_true_on_success(): void
    {
        $categoryType = $this->createCategoryType('blog-categories');
        $category = $this->createCategory($categoryType, ['name' => 'First']);

        $request = Request::create('/admin/categories/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminCategoriesController::class)->destroy($categoryType, $category, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function categories_destroy_returns_json_false_and_500_when_delete_fails(): void
    {
        $categoryType = $this->createCategoryType('blog-categories');
        $category = $this->createCategory($categoryType, ['name' => 'First']);

        $category->delete();

        $request = Request::create('/admin/categories/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminCategoriesController::class)->destroy($categoryType, $category, $request);

        $this->assertSame(500, $response->status());
        $this->assertSame('false', $response->getContent());
    }

    #[Test]
    public function category_binding_query_throws_when_category_does_not_belong_to_category_type(): void
    {
        $blogType = $this->createCategoryType('blog-categories');
        $staffType = $this->createCategoryType('staff-categories');
        $staffCategory = $this->createCategory($staffType, ['name' => 'Staff']);

        $this->expectException(ModelNotFoundException::class);

        $this->resolveCategoryForTypeOrFail($blogType, $staffCategory->id);
    }

    #[Test]
    public function categories_bulk_rejects_category_ids_from_other_category_type(): void
    {
        $blogType = $this->createCategoryType('blog-categories');
        $staffType = $this->createCategoryType('staff-categories');
        $staffCategory = $this->createCategory($staffType, ['name' => 'Staff']);

        $this->expectException(ValidationException::class);

        $request = Request::create('/admin/categories/bulk', 'PATCH', [
            'action' => 'delete',
            'categories' => [$staffCategory->id],
        ]);

        app(AdminCategoriesController::class)->bulk($blogType, $request);
    }

    #[Test]
    public function categories_store_persists_payload_and_redirects_to_edit(): void
    {
        $type = $this->createCategoryType('blog-categories');

        $request = \Mockery::mock(CategoriesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Policy',
            'slug' => 'policy',
        ]);
        $request->shouldReceive('input')->with('slug')->andReturn('policy');
        $request->shouldReceive('input')->with('lang', \Mockery::any())->andReturn('dv');
        $request->shouldReceive('has')->with('icon')->andReturn(false);
        $request->shouldReceive('has')->with('color')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(AdminCategoriesController::class)->store($type, $request);

        $created = Category::query()->where('slug', 'policy')->firstOrFail();
        $this->assertSame($type->id, $created->type_id);
        $this->assertSame('dv', $created->lang);
        $this->assertSame(route('admin.categories.edit', [$type, $created]), $response->getTargetUrl());
    }

    #[Test]
    public function categories_update_changes_parent_slug_and_optional_attributes(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $parent = $this->createCategory($type, ['name' => 'Parent', 'slug' => 'parent']);
        $category = $this->createCategory($type, ['name' => 'Child', 'slug' => 'child']);

        $request = \Mockery::mock(CategoriesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Updated Child',
            'slug' => 'updated-child',
        ]);
        $request->shouldReceive('input')->with('slug')->andReturn('updated-child');
        $request->shouldReceive('has')->with('parent')->andReturn(true);
        $request->shouldReceive('input')->with('parent')->andReturn($parent->id);
        $request->shouldReceive('has')->with('icon')->andReturn(true);
        $request->shouldReceive('input')->with('icon')->andReturn('ri-folder-line');
        $request->shouldReceive('has')->with('color')->andReturn(true);
        $request->shouldReceive('input')->with('color')->andReturn('#336699');
        $request->shouldReceive('input')->with('hide_translation', false)->andReturn(true);
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(AdminCategoriesController::class)->update($type, $request, $category);

        $category->refresh();

        $this->assertSame('updated-child', $category->slug);
        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame(route('admin.categories.edit', [$type, $category]), $response->getTargetUrl());
    }

    #[Test]
    public function categories_bulk_deletes_matching_categories_and_redirects_to_index(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $first = $this->createCategory($type, ['name' => 'First', 'slug' => 'first']);
        $second = $this->createCategory($type, ['name' => 'Second', 'slug' => 'second']);

        $request = Request::create('/admin/categories/bulk', 'PATCH', [
            'action' => 'delete',
            'categories' => [$first->id, $second->id],
        ]);

        $response = app(AdminCategoriesController::class)->bulk($type, $request);

        $this->assertSame(302, $response->status());
        $this->assertSame(route('admin.categories.index', $type), $response->getTargetUrl());
        $this->assertDatabaseMissing('categories', ['id' => $first->id]);
        $this->assertDatabaseMissing('categories', ['id' => $second->id]);
    }

    private function resolveCategoryForTypeOrFail(CategoryType $categoryType, int $categoryId): Category
    {
        return Category::query()->where('type_id', $categoryType->id)->findOrFail($categoryId);
    }

    private function createCategoryType(string $slug): CategoryType
    {
        $categoryType = new CategoryType([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'singular_name' => ucfirst(rtrim(str_replace('-', ' ', $slug), 's')),
            'slug' => $slug,
        ]);

        $categoryType->lang = 'en';
        $categoryType->save();

        return $categoryType;
    }

    private function createCategory(CategoryType $type, array $attributes = []): Category
    {
        $category = new Category(array_merge([
            'name' => 'Category ' . fake()->unique()->numberBetween(1, 99999),
            'slug' => 'category-' . fake()->unique()->numberBetween(1, 99999),
        ], $attributes));

        $category->type_id = $type->id;
        $category->lang = $attributes['lang'] ?? 'en';
        $category->save();

        return $category;
    }
}
