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

        Route::get('/_test/admin/category-types/create', fn () => 'ok')->name('admin.category-types.create');
        Route::get('/_test/admin/category-types/{category_type}/edit', fn () => 'ok')->name('admin.category-types.edit');
        Route::get('/_test/admin/category-types/{category_type}', fn () => 'ok')->name('admin.category-types.show');
        Route::get('/_test/admin/category-types', fn () => 'ok')->name('admin.category-types.index');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function category_types_index_create_show_and_edit_return_expected_responses(): void
    {
        $first = $this->createCategoryType('announcements');
        $second = $this->createCategoryType('press-releases');

        $controller = \Mockery::mock(AdminCategoryTypesController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('getOrderBy')->andReturn('created_at');
        $controller->shouldReceive('getOrder')->andReturn('asc');
        $controller->shouldReceive('getPerPage')->andReturn(10);

        $index = $controller->index(Request::create('/admin/category-types', 'GET', [
            'per_page' => 10,
        ]));
        $create = $controller->create(Request::create('/admin/category-types/create', 'GET'));
        $show = $controller->show($first);
        $edit = $controller->edit($second);

        $this->assertSame('cms::admin.category-types.index', $index->name());
        $this->assertSame([$first->id, $second->id], $index->getData()['category_types']->pluck('id')->all());
        $this->assertSame('cms::admin.category-types.create', $create->name());
        $this->assertSame(route('admin.category-types.edit', $first), $show->getTargetUrl());
        $this->assertSame('cms::admin.category-types.edit', $edit->name());
        $this->assertTrue($edit->getData()['category_type']->is($second));
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
        $controller->shouldReceive('authorize')->once()->with('delete', \Mockery::on(
            fn (CategoryType $categoryType) => $categoryType->is($type)
        ))->andThrow(new AuthorizationException());

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
    public function category_types_store_uses_the_requested_language_when_translations_are_enabled(): void
    {
        config()->set('cms.should_translate', true);

        $request = \Mockery::mock(CategoryTypesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'Blog Categories',
            'singular_name' => 'Blog Category',
            'slug' => 'blog-categories',
        ]);
        $request->shouldReceive('input')->with('lang', \Mockery::any())->andReturn('en');

        $response = app(AdminCategoryTypesController::class)->store($request);

        $created = CategoryType::query()->where('slug', 'blog-categories')->firstOrFail();
        $this->assertSame(route('admin.category-types.edit', $created), $response->getTargetUrl());
        $this->assertSame('Blog Categories', $created->name);
        $this->assertSame('en', $created->lang);
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

    #[Test]
    public function category_types_update_sets_lang_for_primary_records_when_translations_are_enabled(): void
    {
        config()->set('cms.should_translate', true);

        $type = $this->createCategoryType('news-categories');
        $type->lang = 'en';
        $type->save();

        $request = \Mockery::mock(CategoryTypesRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'name' => 'News Topics',
            'singular_name' => 'News Topic',
            'slug' => 'news-topics',
        ]);
        $request->shouldReceive('input')->with('is_translation')->andReturn(false);
        $request->shouldReceive('input')->with('lang')->andReturn('dv');
        $request->shouldReceive('input')->with('slug')->andReturn('news-topics');

        $response = app(AdminCategoryTypesController::class)->update($request, $type);

        $type->refresh();

        $this->assertSame('dv', $type->lang);
        $this->assertSame('news-topics', $type->slug);
        $this->assertSame(route('admin.category-types.edit', $type), $response->getTargetUrl());
    }

    #[Test]
    public function category_types_bulk_deletes_matching_records_and_redirects_to_index(): void
    {
        $first = $this->createCategoryType('bulk-one');
        $second = $this->createCategoryType('bulk-two');

        $request = Request::create('/admin/category-types/bulk', 'PATCH', [
            'action' => 'delete',
            'category_types' => [$first->id, $second->id],
        ]);

        $response = app(AdminCategoryTypesController::class)->bulk($request);

        $this->assertSame(302, $response->status());
        $this->assertSame(route('admin.category-types.index'), $response->getTargetUrl());
        $this->assertDatabaseMissing('category_types', ['id' => $first->id]);
        $this->assertDatabaseMissing('category_types', ['id' => $second->id]);
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
