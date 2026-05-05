<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Http\Controllers\Admin\CategoriesController as AdminCategoriesController;
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
