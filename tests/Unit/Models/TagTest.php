<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Models\Tag;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Route::get('/admin/tags/{tag}', fn (Tag $tag) => $tag->id)->name('admin.tags.show');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function it_sanitizes_names_builds_slugs_and_filters_by_slug(): void
    {
        $tag = new Tag(['name' => 'budget updates']);
        $tag->lang = 'en';
        $tag->save();

        $this->assertSame('budget-updates', $tag->slug);
        $this->assertSame('needs review', Tag::sanitizeName('Needs Review'));
        $this->assertSame([$tag->id], Tag::query()->hasSlug('budget-updates')->pluck('id')->all());
        $this->assertSame(route('admin.tags.show', $tag), $tag->admin_url);
        $this->assertSame($tag->admin_url, $tag->getAdminLocalizedUrl());
    }
}
