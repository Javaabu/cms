<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Http\Requests\CategoriesRequest;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Translatable\Http\Controllers\Admin\CategoriesController as TranslatableAdminCategoriesController;
use PHPUnit\Framework\Attributes\Test;

class TranslatableAdminCategoriesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Gate::shouldReceive('authorize')->andReturn(true);

        Route::get('/_test/admin/{language}/category-types/{category_type}', [TranslatableAdminCategoriesController::class, 'index'])->name('admin.categories.index');
        Route::get('/_test/admin/{language}/category-types/{category_type}/create', [TranslatableAdminCategoriesController::class, 'create'])->name('admin.categories.create');
        Route::get('/_test/admin/{language}/category-types/{category_type}/{category}/edit', [TranslatableAdminCategoriesController::class, 'edit'])->name('admin.categories.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function index_create_show_and_edit_return_expected_responses(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $first = $this->createCategory($type);
        $second = $this->createCategory($type);

        $controller = \Mockery::mock(TranslatableAdminCategoriesController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('getOrderBy')->andReturn('created_at');
        $controller->shouldReceive('getOrder')->andReturn('asc');
        $controller->shouldReceive('getPerPage')->andReturn(10);

        $index = $controller->index('en', $type, Request::create('/admin/translatable/categories', 'GET'));
        $create = $controller->create('en', $type, Request::create('/admin/translatable/categories/create', 'GET'));
        $show = $controller->show('en', $type, $first);
        $edit = $controller->edit('en', $type, $second);

        $this->assertSame('cms::admin.categories.index', $index->name());
        $this->assertSame([$first->id, $second->id], $index->getData()['categories']->pluck('id')->all());
        $this->assertSame('cms::admin.categories.create', $create->name());
        $this->assertSame(action([TranslatableAdminCategoriesController::class, 'edit'], ['en', $type, $first]), $show->getTargetUrl());
        $this->assertSame('cms::admin.categories.edit', $edit->name());
        $this->assertTrue($edit->getData()['category']->is($second));
    }

    #[Test]
    public function destroy_returns_json_true_on_success(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $category = $this->createCategory($type);

        $request = Request::create('/admin/translatable/categories/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(TranslatableAdminCategoriesController::class)->destroy('en', $type, $category, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function bulk_rejects_category_ids_from_other_type(): void
    {
        $blogType = $this->createCategoryType('blog-categories');
        $staffType = $this->createCategoryType('staff-categories');
        $staffCategory = $this->createCategory($staffType);

        $this->expectException(ValidationException::class);

        $request = Request::create('/admin/translatable/categories/bulk', 'PATCH', [
            'action' => 'delete',
            'categories' => [$staffCategory->id],
        ]);

        app(TranslatableAdminCategoriesController::class)->bulk('en', $blogType, $request);
    }

    #[Test]
    public function bulk_throws_when_view_any_authorization_fails(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $controller = \Mockery::mock(TranslatableAdminCategoriesController::class)->makePartial();
        $controller->shouldReceive('authorize')->once()->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/translatable/categories/bulk', 'PATCH', [
            'action' => 'delete',
            'categories' => [],
        ]);

        $controller->bulk('en', $type, $request);
    }

    #[Test]
    public function store_persists_payload_and_redirects_to_edit(): void
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
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(TranslatableAdminCategoriesController::class)->store('en', $type, $request);

        $created = Category::query()->where('slug', 'policy')->firstOrFail();
        $this->assertSame('dv', $created->lang);
        $this->assertSame(action([TranslatableAdminCategoriesController::class, 'edit'], ['en', $type, $created]), $response->getTargetUrl());
    }

    #[Test]
    public function update_changes_parent_and_redirects_to_edit(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $parent = $this->createCategory($type);
        $category = $this->createCategory($type);

        $request = \Mockery::mock(CategoriesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
        $request->shouldReceive('input')->with('slug')->andReturn('updated');
        $request->shouldReceive('has')->with('parent')->andReturn(true);
        $request->shouldReceive('input')->with('parent')->andReturn($parent->id);
        $request->shouldReceive('has')->with('icon')->andReturn(false);
        $request->shouldReceive('has')->with('color')->andReturn(false);
        $request->shouldReceive('input')->with('hide_translation', false)->never();
        $request->shouldReceive('input')->with('featured_image')->andReturn(null);
        $request->shouldReceive('input')->with('clear_file')->andReturn(null);
        $request->shouldReceive('file')->andReturn(null);

        $response = app(TranslatableAdminCategoriesController::class)->update('en', $type, $request, $category);

        $category->refresh();

        $this->assertSame('updated', $category->slug);
        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame(action([TranslatableAdminCategoriesController::class, 'edit'], ['en', $type, $category]), $response->getTargetUrl());
    }

    #[Test]
    public function bulk_deletes_matching_categories_and_redirects_to_index(): void
    {
        $type = $this->createCategoryType('blog-categories');
        $first = $this->createCategory($type);
        $second = $this->createCategory($type);

        $request = Request::create('/admin/translatable/categories/bulk', 'PATCH', [
            'action' => 'delete',
            'categories' => [$first->id, $second->id],
        ]);

        $response = app(TranslatableAdminCategoriesController::class)->bulk('en', $type, $request);

        $this->assertSame(302, $response->status());
        $this->assertSame(action([TranslatableAdminCategoriesController::class, 'index'], ['en', $type]), $response->getTargetUrl());
        $this->assertDatabaseMissing('categories', ['id' => $first->id]);
        $this->assertDatabaseMissing('categories', ['id' => $second->id]);
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

    private function createCategory(CategoryType $type): Category
    {
        $category = new Category([
            'name' => 'Category ' . fake()->unique()->numberBetween(1, 99999),
            'slug' => 'category-' . fake()->unique()->numberBetween(1, 99999),
        ]);
        $category->type_id = $type->id;
        $category->lang = 'en';
        $category->save();
        return $category;
    }
}
