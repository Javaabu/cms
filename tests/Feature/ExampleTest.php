<?php

namespace Javaabu\Cms\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_make_a_blank_post_object(): void
    {
        $post = new Post();

        $this->assertInstanceOf(Post::class, $post);
    }

}
