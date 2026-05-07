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

namespace Javaabu\Cms\Tests\Unit\Media {
    use Illuminate\Validation\Rules\Exists;
    use Javaabu\Cms\Media\AllowedMimeTypes;
    use Javaabu\Cms\Tests\TestCase;
    use PHPUnit\Framework\Attributes\Test;

    class AllowedMimeTypesTest extends TestCase
    {
        #[Test]
        public function it_resolves_allowed_types_icons_extensions_and_flattened_mime_lists(): void
        {
            $this->assertContains('image', AllowedMimeTypes::getAllowedTypes());
            $this->assertContains('application/pdf', AllowedMimeTypes::getAllowedMimeTypes());
            $this->assertContains('image/webp', AllowedMimeTypes::getAllowedMimeTypes('image'));

            $this->assertSame('image', AllowedMimeTypes::getType('image/png'));
            $this->assertSame('pdf', AllowedMimeTypes::getType('application/pdf'));
            $this->assertNull(AllowedMimeTypes::getType('application/x-unknown-cms-test'));

            $this->assertSame('file-image', AllowedMimeTypes::getIcon('image/png'));
            $this->assertSame('collection-pdf', AllowedMimeTypes::getIcon('application/pdf'));
            $this->assertSame('file-pdf', AllowedMimeTypes::getWebIcon('application/pdf'));
            $this->assertSame('xlsx', AllowedMimeTypes::getExtension('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        }

        #[Test]
        public function it_builds_validation_rules_for_uploads_and_attachment_ids(): void
        {
            $imageRule = AllowedMimeTypes::getValidationRule('image');
            $documentRule = AllowedMimeTypes::getValidationRule('document', false);
            $attachmentRule = AllowedMimeTypes::getAttachmentValidationRule('pdf');

            $this->assertSame('nullable', $imageRule[0]);
            $this->assertStringStartsWith('mimetypes:image/jpeg,image/png', $imageRule[1]);
            $this->assertSame('max:2048', $imageRule[2]);

            $this->assertStringContainsString('mimetypes:', $documentRule);
            $this->assertStringContainsString('application/pdf', $documentRule);
            $this->assertStringContainsString('max:4096', $documentRule);

            $this->assertSame('nullable', $attachmentRule[0]);
            $this->assertInstanceOf(Exists::class, $attachmentRule[1]);
        }

        #[Test]
        public function it_checks_mime_type_membership_within_a_specific_type(): void
        {
            $this->assertTrue(AllowedMimeTypes::isAllowedMimeType('image/jpeg', 'image'));
            $this->assertFalse(AllowedMimeTypes::isAllowedMimeType('video/mp4', 'image'));
            $documentTypes = AllowedMimeTypes::getAllowedMimeTypesString('document', '|');

            $this->assertStringContainsString('application/pdf', $documentTypes);
            $this->assertStringContainsString('application/msword', $documentTypes);
            $this->assertStringContainsString('image/jpeg', $documentTypes);
        }
    }
}
