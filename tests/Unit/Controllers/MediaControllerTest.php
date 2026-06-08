<?php

namespace Javaabu\Cms\Tests\Unit\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Javaabu\Helpers\Exceptions\AppException;
use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\Admin\MediaController;
use Javaabu\Cms\Http\Requests\MediaRequest;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MediaControllerTest extends TestCase
{
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
}
