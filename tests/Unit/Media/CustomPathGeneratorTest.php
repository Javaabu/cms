<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Javaabu\Cms\Media\CustomPathGenerator;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FakeHashidsConnection
{
    public array $encodedValues = [];

    public function encode($value): string
    {
        $this->encodedValues[] = $value;

        return "encoded-{$value}";
    }
}

class FakeHashidsFacade
{
    public static ?FakeHashidsConnection $connection = null;

    public static array $requestedConnections = [];

    public static function connection(string $name): FakeHashidsConnection
    {
        self::$requestedConnections[] = $name;

        return self::$connection ?? throw new \RuntimeException('Hashids connection was not configured.');
    }
}

class CustomPathGeneratorTest extends TestCase
{
    #[Test]
    public function it_builds_upload_paths_from_the_uploads_hashids_connection(): void
    {
        if (! class_exists('Vinkla\\Hashids\\Facades\\Hashids', false)) {
            class_alias(FakeHashidsFacade::class, 'Vinkla\\Hashids\\Facades\\Hashids');
        }

        FakeHashidsFacade::$requestedConnections = [];
        FakeHashidsFacade::$connection = new FakeHashidsConnection;

        $media = new Media;
        $media->id = 42;

        $generator = new CustomPathGenerator;

        $this->assertSame('encoded-42/', $generator->getPath($media));
        $this->assertSame('encoded-42/c/', $generator->getPathForConversions($media));
        $this->assertSame('encoded-42/cri/', $generator->getPathForResponsiveImages($media));
        $this->assertSame(['uploads', 'uploads', 'uploads'], FakeHashidsFacade::$requestedConnections);
        $this->assertSame([42, 42, 42], FakeHashidsFacade::$connection->encodedValues);
    }
}
