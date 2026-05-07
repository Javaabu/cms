<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Javaabu\Cms\Media\CustomPathGenerator;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CustomPathGeneratorTest extends TestCase
{
    #[Test]
    public function it_builds_upload_paths_from_the_uploads_hashids_connection(): void
    {
        $media = new Media;
        $media->id = 42;

        $generator = new CustomPathGenerator;

        $this->assertSame('42/', $generator->getPath($media));
        $this->assertSame('42/c/', $generator->getPathForConversions($media));
        $this->assertSame('42/cri/', $generator->getPathForResponsiveImages($media));
    }
}
