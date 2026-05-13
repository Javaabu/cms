<?php

namespace Javaabu\Cms\Tests\Unit\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Javaabu\Cms\Http\Controllers\Admin\MediaController;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MediaControllerTest extends TestCase
{
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
}

