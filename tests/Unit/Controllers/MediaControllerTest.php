<?php

namespace Javaabu\Cms\Tests\Unit\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Javaabu\Helpers\Exceptions\AppException;
use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\Admin\MediaController;
use Javaabu\Cms\Http\Requests\MediaRequest;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('model');
                $table->uuid('uuid')->nullable()->unique();
                $table->string('collection_name')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->string('disk')->nullable();
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->json('manipulations')->nullable();
                $table->json('custom_properties')->nullable();
                $table->json('generated_conversions')->nullable();
                $table->json('responsive_images')->nullable();
                $table->unsignedInteger('order_column')->nullable();
                $table->json('translations')->nullable();
                $table->string('lang')->nullable();
                $table->boolean('hide_translation')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('media_controller_users')) {
            Schema::create('media_controller_users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        config()->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', MediaControllerUser::class);
        Relation::morphMap(['user' => MediaControllerUser::class]);
        \Illuminate\Support\Facades\Route::get('/admin/media/{media}/edit', fn () => 'edit')->name('admin.media.edit');
        \Illuminate\Support\Facades\Route::get('/admin/media', fn () => 'index')->name('admin.media.index');
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function create_show_and_edit_return_expected_responses(): void
    {
        $media = \Mockery::mock(Media::class)->makePartial();
        $media->shouldReceive('url')->with('edit')->once()->andReturn('http://localhost/media/1/edit');
        $media->shouldReceive('dontShowTranslationFallbacks')->once();

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $create = $controller->create();
        $show = $controller->show($media);
        $edit = $controller->edit($media);

        $this->assertSame('cms::admin.media.create', $create->name());
        $this->assertSame('http://localhost/media/1/edit', $show->getTargetUrl());
        $this->assertSame('cms::admin.media.edit', $edit->name());
    }

    #[Test]
    public function destroy_returns_json_true_on_successful_delete(): void
    {
        $media = \Mockery::mock(Media::class);
        $media->shouldReceive('delete')->once()->andReturn(true);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $request = Request::create('/admin/media/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $controller->destroy($media, $request);

        $this->assertSame(200, $response->status());
        $this->assertSame('true', $response->getContent());
    }

    #[Test]
    public function destroy_returns_json_false_and_500_when_delete_fails(): void
    {
        $media = \Mockery::mock(Media::class);
        $media->shouldReceive('delete')->once()->andReturn(false);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $request = Request::create('/admin/media/delete', 'DELETE', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $controller->destroy($media, $request);

        $this->assertSame(500, $response->status());
        $this->assertSame('false', $response->getContent());
    }

    #[Test]
    public function destroy_redirects_back_to_the_index_for_browser_requests(): void
    {
        $media = \Mockery::mock(Media::class);
        $media->shouldReceive('delete')->once()->andReturn(true);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $request = Request::create('/admin/media/1', 'DELETE');
        $response = $controller->destroy($media, $request);

        $this->assertSame(302, $response->status());
        $this->assertStringContainsString('/admin/media', $response->getTargetUrl());
    }

    #[Test]
    public function index_filters_user_visible_media_and_preserves_search_view_and_type_inputs(): void
    {
        auth()->setUser(new MediaControllerUser(7, ['create']));

        $visiblePdf = $this->createMedia([
            'name' => 'Budget Report',
            'mime_type' => 'application/pdf',
            'model_type' => MediaControllerUser::class,
            'model_id' => 7,
        ]);
        $this->createMedia([
            'name' => 'Gallery Image',
            'mime_type' => 'image/png',
            'model_type' => MediaControllerUser::class,
            'model_id' => 7,
        ]);
        $this->createMedia([
            'name' => 'Other User Report',
            'mime_type' => 'application/pdf',
            'model_type' => MediaControllerUser::class,
            'model_id' => 9,
        ]);

        $controller = new TestableMediaController();

        $view = $controller->index(Request::create('/admin/media', 'GET', [
            'search' => 'Budget',
            'type' => 'pdf',
            'view' => 'list',
            'per_page' => 10,
            'orderby' => 'name',
            'order' => 'asc',
        ]));

        $this->assertSame('cms::admin.media.index', $view->name());
        $this->assertSame([$visiblePdf->id], $view->getData()['media_items']->pluck('id')->all());
        $this->assertSame('Budget', $view->getData()['search']);
        $this->assertSame('list', $view->getData()['view']);
        $this->assertSame(10, $view->getData()['per_page']);
    }

    #[Test]
    public function picker_limits_results_to_user_media_and_normalizes_single_selected_items(): void
    {
        auth()->setUser(new MediaControllerUser(7, ['create', 'edit_other_users_media']));

        $first = $this->createMedia([
            'name' => 'Brochure',
            'mime_type' => 'application/pdf',
            'model_type' => 'user',
            'model_id' => 7,
        ]);
        $second = $this->createMedia([
            'name' => 'Second Brochure',
            'mime_type' => 'application/pdf',
            'model_type' => 'user',
            'model_id' => 7,
        ]);
        $this->createMedia([
            'name' => 'Other Type',
            'mime_type' => 'image/png',
            'model_type' => 'user',
            'model_id' => 7,
        ]);

        $controller = new TestableMediaController();

        $view = $controller->picker(Request::create('/admin/media/picker', 'GET', [
            'search' => 'Brochure',
            'type' => 'pdf',
            'single' => true,
            'selected' => [$first->id, $second->id],
        ]));

        $this->assertSame('cms::admin.media.picker.show', $view->name());
        $this->assertSame([$first->id], $view->getData()['selected']);
        $this->assertTrue($view->getData()['single']);
        $this->assertSame([$first->id, $second->id], $view->getData()['media_items']->pluck('id')->all());
        $this->assertSame('Brochure', $view->getData()['search']);
    }

    #[Test]
    public function bulk_throws_when_create_authorization_fails(): void
    {
        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();
        $controller->shouldReceive('authorize')->once()->andThrow(new AuthorizationException());

        $this->expectException(AuthorizationException::class);

        $request = Request::create('/admin/media/bulk', 'PATCH', [
            'action' => 'delete',
            'media' => [],
        ]);

        $controller->bulk($request);
    }

    #[Test]
    public function store_throws_when_no_file_is_uploaded(): void
    {
        $request = \Mockery::mock(MediaRequest::class)->makePartial();
        $request->shouldReceive('user')->once()->andReturnNull();
        $request->shouldReceive('file')->with('file')->andReturn(null);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $this->expectException(AppException::class);

        $controller->store($request);
    }

    #[Test]
    public function store_returns_a_detailed_json_payload_when_the_upload_succeeds(): void
    {
        $file = new class {
            public function guessExtension(): string
            {
                return 'png';
            }
        };

        $savedMedia = \Mockery::mock(Media::class)->makePartial();
        $savedMedia->id = 12;
        $savedMedia->name = 'Hero';
        $savedMedia->file_name = 'hero.png';
        $savedMedia->mime_type = 'image/png';
        $savedMedia->shouldReceive('getUrl')->with('preview')->andReturn('/preview');
        $savedMedia->shouldReceive('getUrl')->with('thumb')->andReturn('/thumb');
        $savedMedia->shouldReceive('getUrl')->with('large')->andReturn('/large');
        $savedMedia->shouldReceive('getUrl')->withNoArgs()->andReturn('/media/hero.png');

        $adder = \Mockery::mock(\Spatie\MediaLibrary\MediaCollections\FileAdder::class);
        $adder->shouldReceive('usingName')->once()->with('Hero')->andReturnSelf();
        $adder->shouldReceive('preservingOriginal')->once()->andReturnSelf();
        $adder->shouldReceive('withResponsiveImages')->once()->andReturnSelf();
        $adder->shouldReceive('usingFileName')->once()->with(\Mockery::on(fn (string $name) => str_ends_with($name, '.png')))->andReturnSelf();
        $adder->shouldReceive('toMediaCollection')->once()->with('media_library')->andReturn($savedMedia);

        $user = \Mockery::mock();
        $user->shouldReceive('addMedia')->once()->with($file)->andReturn($adder);

        $request = \Mockery::mock(MediaRequest::class)->makePartial();
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('file')->with('file')->andReturn($file);
        $request->shouldReceive('input')->with('name')->andReturn('Hero');
        $request->shouldReceive('anyFilled')->with(['description'])->andReturn(true);
        $request->shouldReceive('input')->with('description')->andReturn('Front page hero');
        $request->shouldReceive('is')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(true);

        $controller = new TestableMediaController();

        $response = $controller->store($request);
        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->status());
        $this->assertTrue($payload['success']);
        $this->assertSame(12, $payload['id']);
        $this->assertSame('/preview', $payload['preview']);
        $this->assertStringContainsString('/admin/media/12/edit', $payload['edit_url']);
    }

    #[Test]
    public function store_redirects_to_edit_for_browser_requests_when_the_upload_succeeds(): void
    {
        $file = new class {
            public function guessExtension(): string
            {
                return 'png';
            }
        };

        $savedMedia = \Mockery::mock(Media::class)->makePartial();
        $savedMedia->id = 22;
        $savedMedia->name = 'Browser Hero';
        $savedMedia->file_name = 'browser-hero.png';
        $savedMedia->mime_type = 'image/png';
        $savedMedia->shouldReceive('url')->with('edit')->andReturn('http://localhost/admin/media/22/edit');

        $adder = \Mockery::mock(\Spatie\MediaLibrary\MediaCollections\FileAdder::class);
        $adder->shouldReceive('usingName')->once()->with('Browser Hero')->andReturnSelf();
        $adder->shouldReceive('preservingOriginal')->once()->andReturnSelf();
        $adder->shouldReceive('withResponsiveImages')->once()->andReturnSelf();
        $adder->shouldReceive('usingFileName')->once()->andReturnSelf();
        $adder->shouldReceive('toMediaCollection')->once()->with('media_library')->andReturn($savedMedia);

        $user = \Mockery::mock();
        $user->shouldReceive('addMedia')->once()->with($file)->andReturn($adder);

        $request = \Mockery::mock(MediaRequest::class)->makePartial();
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('file')->with('file')->andReturn($file);
        $request->shouldReceive('input')->with('name')->andReturn('Browser Hero');
        $request->shouldReceive('anyFilled')->with(['description'])->andReturn(false);
        $request->shouldReceive('is')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(false);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();
        $controller->shouldReceive('flashSuccessMessage')->once()->andReturnNull();

        $response = $controller->store($request);

        $this->assertSame(302, $response->status());
        $this->assertSame('http://localhost/admin/media/22/edit', $response->getTargetUrl());
    }

    #[Test]
    public function update_redirects_to_edit_for_non_translation_browser_requests(): void
    {
        $media = \Mockery::mock(Media::class)->makePartial();
        $media->shouldReceive('fill')->once()->with(['name' => 'Updated', 'description' => 'Updated description'])->andReturnSelf();
        $media->shouldReceive('save')->once()->andReturn(true);
        $media->shouldReceive('url')->with('edit')->once()->andReturn('http://localhost/media/1/edit');

        $request = \Mockery::mock(MediaRequest::class)->makePartial();
        $request->shouldReceive('is')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('input')->with('is_translation')->andReturn(false);
        $request->shouldReceive('input')->with('lang')->andReturn(null);
        $request->shouldReceive('input')->with('translation')->twice()->andReturn(false);
        $request->shouldReceive('only')->with(['name', 'description'])->andReturn([
            'name' => 'Updated',
            'description' => 'Updated description',
        ]);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();
        $controller->shouldReceive('flashSuccessMessage')->once()->andReturnNull();

        $response = $controller->update($request, $media);

        $this->assertSame(302, $response->status());
        $this->assertSame('http://localhost/media/1/edit', $response->getTargetUrl());
    }

    #[Test]
    public function destroy_aborts_for_browser_requests_when_delete_fails(): void
    {
        $media = \Mockery::mock(Media::class);
        $media->shouldReceive('delete')->once()->andReturn(false);

        $controller = \Mockery::mock(MediaController::class)->makePartial();
        $controller->shouldReceive('authorizeResource')->andReturnNull();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $controller->destroy($media, Request::create('/admin/media/1', 'DELETE'));
    }

    #[Test]
    public function update_saves_translation_payloads_and_redirects_back_for_browser_requests(): void
    {
        $media = \Mockery::mock(Media::class)->makePartial();
        $media->translations = [];
        $media->hide_translation = false;
        $media->shouldReceive('save')->once()->andReturn(true);
        $media->shouldReceive('getTranslatables')->andReturn(['description', 'name']);

        $request = \Mockery::mock(MediaRequest::class)->makePartial();
        $request->shouldReceive('is')->andReturn(false);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('input')->with('is_translation')->andReturn(false);
        $request->shouldReceive('input')->with('lang')->andReturn('dv');
        $request->shouldReceive('input')->with('translation')->twice()->andReturn(true);
        $request->shouldReceive('only')->with(['description', 'name'])->andReturn([
            'description' => 'Translated description',
            'name' => 'Translated name',
        ]);
        $request->shouldReceive('input')->with('hide_translation', false)->andReturn(true);
        $request->shouldReceive('session')->andReturn(new class {
            public function flash($key, $value = true): void {}
        });

        $controller = new TestableMediaController();

        $response = $controller->update($request, $media);

        $this->assertSame([
            'description' => 'Translated description',
            'name' => 'Translated name',
        ], $media->translations);
        $this->assertTrue($media->hide_translation);
        $this->assertSame(302, $response->status());
    }

    private function createMedia(array $attributes = []): Media
    {
        $media = new Media([
            'name' => $attributes['name'] ?? 'Default Media',
        ]);
        $media->file_name = $attributes['file_name'] ?? 'default.pdf';
        $media->mime_type = $attributes['mime_type'] ?? 'application/pdf';
        $media->disk = $attributes['disk'] ?? 'public';
        $media->conversions_disk = $attributes['conversions_disk'] ?? 'public';
        $media->size = $attributes['size'] ?? 100;
        $media->manipulations = $attributes['manipulations'] ?? [];
        $media->custom_properties = $attributes['custom_properties'] ?? [];
        $media->generated_conversions = $attributes['generated_conversions'] ?? [];
        $media->responsive_images = $attributes['responsive_images'] ?? [];
        $media->collection_name = $attributes['collection_name'] ?? 'documents';
        $media->model_type = $attributes['model_type'] ?? MediaControllerUser::class;
        $media->model_id = $attributes['model_id'] ?? 1;
        $media->save();

        return $media;
    }
}

class MediaControllerUser extends User
{
    public function __construct(public int $id = 0, private array $permissions = [])
    {
        parent::__construct();
    }

    public function can($abilities, $arguments = []): bool
    {
        return in_array($abilities, $this->permissions, true);
    }

    public function getMorphClass()
    {
        return static::class;
    }
}

class TestableMediaController extends MediaController
{
    public function __construct()
    {
    }

    public function authorize($ability, $arguments = [])
    {
        return null;
    }

    protected function getPerPage(Request $request, int $default = 0): int
    {
        return (int) ($request->input('per_page', $default) ?: $default);
    }

    public function flashSuccessMessage(): void
    {
    }

    public function validate($request, array $rules, ...$params)
    {
        return validator($request->all(), $rules)->validate();
    }

    public function redirect($request, $to)
    {
        return redirect($to);
    }
}
