<?php

namespace Javaabu\Cms\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\RootSlugsRegistrar;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RootSlugsRegistrarAliasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function root_registrar_caches_non_root_post_type_slugs_and_uses_configured_cache_store(): void
    {
        config()->set('cms.rootslugs.cache.key', 'cms-root-slugs-alias-test');
        config()->set('cms.rootslugs.cache.expiration_time', 60);
        config()->set('cms.rootslugs.cache.store', 'missing-store');

        $news = $this->createPostType('news', ['categories' => true]);
        $this->createPostType('home', ['root-page' => true]);

        $registrar = new RootSlugsRegistrar(app('cache'));

        $this->assertSame('cms-root-slugs-alias-test', RootSlugsRegistrar::$cache_key);
        $this->assertSame(60, RootSlugsRegistrar::$cache_expiration_time);
        $this->assertInstanceOf(ArrayStore::class, $registrar->getCacheStore());
        $this->assertSame([$news->id], $registrar->getSlugs()['post_type']->pluck('id')->all());

        $this->createPostType('events', ['categories' => true]);

        $this->assertSame([$news->id], $registrar->getSlugs()['post_type']->pluck('id')->all());

        $registrar->forgetCachedRootSlugs();

        $this->assertSame(['news', 'events'], $registrar->getSlugs()['post_type']->pluck('slug')->all());
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
