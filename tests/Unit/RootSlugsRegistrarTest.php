<?php

namespace Javaabu\Cms\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\RootSlugs\RootSlugsRegistrar;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RootSlugsRegistrarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_root_slug_cache_settings_from_the_cms_config_namespace(): void
    {
        config()->set('cms.rootslugs.cache.key', 'cms-root-slugs-test');
        config()->set('cms.rootslugs.cache.expiration_time', 123);
        config()->set('cms.rootslugs.cache.store', 'array');

        $registrar = new RootSlugsRegistrar(app('cache'));

        $this->assertSame('cms-root-slugs-test', RootSlugsRegistrar::$cache_key);
        $this->assertSame(123, RootSlugsRegistrar::$cache_expiration_time);
        $this->assertInstanceOf(ArrayStore::class, $registrar->getCacheStore());
    }

    #[Test]
    public function it_caches_post_types_that_are_not_root_pages_and_can_forget_the_cache(): void
    {
        config()->set('cms.rootslugs.cache.key', 'cms-root-slugs-post-types-test');
        config()->set('cms.rootslugs.cache.store', 'array');

        $listing = $this->createPostType('news', ['categories' => true]);
        $this->createPostType('home', ['root-page' => true]);

        $registrar = new RootSlugsRegistrar(app('cache'));

        $slugs = $registrar->getSlugs();

        $this->assertSame([$listing->id], $slugs['post_type']->pluck('id')->all());

        $this->createPostType('announcements', ['categories' => true]);

        $this->assertSame([$listing->id], $registrar->getSlugs()['post_type']->pluck('id')->all());

        $registrar->forgetCachedRootSlugs();

        $this->assertSame(
            ['news', 'announcements'],
            $registrar->getSlugs()['post_type']->pluck('slug')->all()
        );
    }

    private function createPostType(string $slug, array $features): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
            'icon' => 'ri-file-line',
            'features' => $features,
        ]);

        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }
}
