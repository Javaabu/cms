<?php

namespace Javaabu\Cms\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteCollection;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\TranslatableCategory;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\TranslatablePost;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslatablePostAdminUrlTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Route::get('/{language}/news/{post_slug}', fn () => 'show')->name('web.post-types.news.show');
        Route::get('/admin/{language}/{post_type}', fn () => 'index')->name('admin.posts.index');
        Route::get('/admin/{language}/{post_type}/{post}', fn () => 'show')->name('admin.posts.show');
        Route::get('/admin/{language}/{post_type}/{post}/edit', fn () => 'edit')->name('admin.posts.edit');
        Route::get('/fallback/{language}/{post_type}/{post}/edit', fn () => 'edit')->name('posts.edit');
        Route::get('/{language}/news', fn () => 'index')->name('web.posts.index.news');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function post_translation_url_includes_locale_post_type_and_post_id(): void
    {
        app()->setLocale('en');

        $postType = $this->createPostType('news');
        $post = $this->createTranslatablePost($postType);

        $this->assertSame(url('/admin/en/news/' . $post->id . '/edit'), $post->url('edit', 'en'));
        $this->assertSame(url('/admin/dv/news/' . $post->id), $post->url('show', 'dv'));
        $this->assertSame(url('/admin/en/news'), $post->url('index', 'en'));
    }

    #[Test]
    public function post_admin_url_is_translated_when_translations_are_enabled(): void
    {
        config()->set('cms.should_translate', true);
        app()->setLocale('dv');

        $postType = $this->createPostType('news');
        $post = $this->createPlainPost($postType);

        $this->assertSame(url('/admin/dv/news/' . $post->slug . '/edit'), $post->admin_url);
    }

    #[Test]
    public function post_admin_url_has_no_locale_prefix_when_translations_are_disabled(): void
    {
        config()->set('cms.should_translate', false);
        app('router')->setRoutes(new RouteCollection());
        Route::get('/fallback/{post_type}/{post}/edit', fn () => 'edit')->name('posts.edit');
        Route::getRoutes()->refreshNameLookups();

        $postType = $this->createPostType('news');
        $post = $this->createPlainPost($postType);

        $this->assertSame(url('/fallback/news/' . $post->slug . '/edit'), $post->admin_url);
    }

    #[Test]
    public function translation_form_uses_model_translation_url_for_edit_translation_button(): void
    {
        $template = file_get_contents(__DIR__ . '/../../../resources/views/admin/components/post-type/form/translations.blade.php');

        $this->assertIsString($template);
        $this->assertStringContainsString('href="{{ $model->translation_url }}"', $template);
        $this->assertStringContainsString("{{ __('Edit Translation') }}", $template);
    }

    #[Test]
    public function translatable_post_urls_include_locale_post_type_and_translation_visibility_rules(): void
    {
        config()->set('cms.should_translate', true);
        app()->setLocale('en');

        $postType = $this->createPostType('news');
        $post = $this->createTranslatablePost($postType);
        $post->translations = ['title' => 'Translated title'];
        $post->save();
        $post->refresh();

        $this->assertSame(url('/en/news/' . $post->slug), $post->permalink);
        $this->assertStringContainsString('/en/news/' . $post->slug, $post->preview_link);
        $this->assertSame(url('/dv/news/' . $post->slug), $post->translatedPermalink('show', 'dv'));
        $this->assertSame(url('/admin/dv/news/' . $post->id . '/edit'), $post->url('edit', 'dv'));
        $this->assertSame($post->url('edit', 'dv'), $post->getAdminLocalizedUrl('dv'));

        $post->hide_translation = true;

        $this->assertNull($post->translatedPermalink('show', 'dv'));
    }

    #[Test]
    public function translatable_category_permalink_uses_available_locale_and_category_query_parameter(): void
    {
        config()->set('cms.should_translate', true);
        app()->setLocale('dv');

        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        $postType = $this->createPostType('news');
        $postType->categoryType()->associate($categoryType);
        $postType->save();

        $category = new TranslatableCategory([
            'name' => 'Policy',
            'slug' => 'policy',
        ]);
        $category->type_id = $categoryType->id;
        $category->lang = Languages::EN;
        $category->translations = ['name' => 'Translated Policy'];
        $category->save();

        $this->assertSame(url('/dv/news?category=' . $category->id), $category->permalink);

        $category->translations = null;

        $this->assertSame(url('/en/news?category=' . $category->id), $category->permalink);
    }

    private function createPostType(string $slug): PostType
    {
        $postType = new PostType([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst($slug),
            'slug' => $slug,
            'icon' => 'ri-file-line',
            'features' => [],
        ]);

        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }

    private function createTranslatablePost(PostType $postType): TranslatablePost
    {
        $post = new TranslatablePost([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-' . fake()->unique()->numberBetween(1, 999999),
            'status' => \Javaabu\Cms\Enums\PostStatus::DRAFT->value,
            'published_at' => now(),
            'content' => 'Test content',
        ]);

        $post->lang = Languages::EN;
        $post->save();

        return $post;
    }

    private function createPlainPost(PostType $postType): Post
    {
        $post = new Post([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'default-' . fake()->unique()->numberBetween(1, 999999),
            'status' => \Javaabu\Cms\Enums\PostStatus::DRAFT->value,
            'published_at' => now(),
            'content' => 'Test content',
        ]);

        $post->lang = Languages::EN;
        $post->save();

        return $post;
    }
}
