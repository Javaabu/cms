<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

