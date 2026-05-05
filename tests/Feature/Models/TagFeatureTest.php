<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Tag;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_find_tag_using_slug_scope(): void
    {
        $matching_tag = $this->create_tag('policy update');
        $this->create_tag('sports news');

        $results = Tag::query()->hasSlug('policy-update')->pluck('id')->all();

        $this->assertSame([$matching_tag->id], $results);
    }

    #[Test]
    public function it_can_sanitize_tag_name(): void
    {
        $sanitized_name = Tag::sanitizeName('Policy   Update');

        $this->assertSame('policy update', $sanitized_name);
    }

    private function create_tag(string $name): Tag
    {
        $tag = new Tag(['name' => $name]);
        $tag->lang = 'en';
        $tag->save();

        return $tag;
    }
}
