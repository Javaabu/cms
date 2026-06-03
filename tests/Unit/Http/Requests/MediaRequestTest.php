<?php

namespace {
    if (! function_exists('get_setting')) {
        function get_setting(string $key): int
        {
            return match ($key) {
                'max_image_file_size' => 2048,
                'max_upload_file_size' => 4096,
                default => 0,
            };
        }
    }
}

namespace Javaabu\Cms\Tests\Unit\Http\Requests {
    use Javaabu\Cms\Http\Requests\MediaRequest;
    use Javaabu\Cms\Tests\TestCase;
    use PHPUnit\Framework\Attributes\Test;

    class MediaRequestTest extends TestCase
    {
        #[Test]
        public function media_request_requires_file_on_create_and_uses_image_limits_for_images(): void
        {
            $request = MediaRequest::create('/media', 'POST', [
                'type' => 'image',
            ]);

            $rules = $request->rules();

            $this->assertContains('required', $rules['file']);
            $this->assertContains('max:2048', $rules['file']);
            $this->assertStringContainsString('mimetypes:image/jpeg,image/png', $rules['file'][0]);
            $this->assertStringStartsWith('nullable|string|in:', $rules['type']);
            $this->assertStringContainsString('image', $rules['type']);
            $this->assertStringContainsString('archive', $rules['type']);
            $this->assertSame('array', $rules['tags']);
            $this->assertSame('string|max:255|required', $rules['tags.*']);
        }

        #[Test]
        public function media_request_keeps_file_optional_on_update_and_uses_upload_limits_for_documents(): void
        {
            $request = MediaRequest::create('/media/1', 'PATCH', [
                'type' => 'document',
            ]);
            $request->setRouteResolver(fn () => new class {
                public function parameter(string $key): mixed
                {
                    return $key === 'media' ? (object) ['id' => 1] : null;
                }
            });

            $rules = $request->rules();

            $this->assertNotContains('required', $rules['file']);
            $this->assertContains('max:4096', $rules['file']);
            $this->assertStringContainsString('application/pdf', $rules['file'][0]);
        }
    }
}
