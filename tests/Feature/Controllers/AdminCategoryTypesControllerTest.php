<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Javaabu\Cms\Http\Controllers\Admin\CategoryTypesController as AdminCategoryTypesController;
use Javaabu\Cms\Http\Requests\CategoryTypesRequest;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminCategoryTypesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Gate::shouldReceive('authorize')->andReturn(true);

        Route::get('/_test/admin/category-types/{category_type}/edit', fn () => 'ok')->name('admin.category-types.edit');
        Route::get('/_test/admin/category-types', fn () => 'ok')->name('admin.category-types.index');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function category_types_destroy_returns_json_true_on_success(): void
    {
        $type = $this->createCategoryType('failing-delete');

        $request = Request::create('/admin/category-types/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(AdminCategoryTypesController::class)->destroy($type, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
        $this->assertDatabaseMissing('category_types', ['id' => $type->id]);
    }

    #[Test]
    public function category_types_bulk_rejects_non_existent_ids(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/admin/category-types/bulk', 'PATCH', [
            'action' => 'delete',
            'category_types' => [999999],
        ]);

        app(AdminCategoryTypesController::class)->bulk($request);
    }

    #[Test]
    public function category_types_bulk_throws_when_view_any_authorization_fails(): void
    {
        $controller = \Mockery::mock(AdminCategoryTypesController::class)->makePartial();
        $controller->shouldReceive('authorize')->once()->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/category-types/bulk', 'PATCH', [
            'action' => 'delete',
            'category_types' => [1],
        ]);

        $controller->bulk($request);
    }

    #[Test]
    public function category_types_bulk_throws_when_delete_authorization_fails(): void
    {
        $type = $this->createCategoryType('bulk-delete-target');

        $controller = \Mockery::mock(AdminCategoryTypesController::class)->makePartial();
        $controller->shouldReceive('authorize')->once()->with('viewAny', CategoryType::class)->andReturn(true);
        $controller->shouldReceive('authorize')->once()->with('delete', CategoryType::class)->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/category-types/bulk', 'PATCH', [
            'action' => 'delete',
            'category_types' => [$type->id],
        ]);

        $controller->bulk($request);
    }

    #[Test]
    public function category_types_store_sets_lang_when_translation_is_enabled(): void
    {
        config()->set('cms.should_translate', true);

        $request = \Mockery::mock(CategoryTypesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Announcements',
            'singular_name' => 'Announcement',
            'slug' => 'announcements',
        ]);
        $request->shouldReceive('input')->with('lang', \Mockery::any())->andReturn('dv');

        $response = app(AdminCategoryTypesController::class)->store($request);

        $created = CategoryType::query()->where('slug', 'announcements')->firstOrFail();
        $this->assertSame('dv', $created->lang);
        $this->assertSame(route('admin.category-types.edit', $created), $response->getTargetUrl());
    }

    #[Test]
    public function category_types_update_does_not_change_lang_for_translation_records(): void
    {
        config()->set('cms.should_translate', true);

        $type = $this->createCategoryType('announcements');
        $type->lang = 'en';
        $type->save();

        $request = \Mockery::mock(CategoryTypesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Announcements Updated',
            'singular_name' => 'Announcement Updated',
            'slug' => 'announcements-updated',
        ]);
        $request->shouldReceive('input')->with('is_translation')->andReturn(true);
        $request->shouldReceive('input')->with('lang')->andReturn('dv');
        $request->shouldReceive('input')->with('slug')->andReturn('announcements-updated');

        $response = app(AdminCategoryTypesController::class)->update($request, $type);

        $type->refresh();

        $this->assertSame('en', $type->lang);
        $this->assertSame('announcements-updated', $type->slug);
        $this->assertSame(route('admin.category-types.edit', $type), $response->getTargetUrl());
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
}
