<?php

namespace Javaabu\Cms\Tests\Feature\Controllers;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Http\Controllers\PostsController as PublicPostsController;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Models\TranslatablePost;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Translatable\Http\Controllers\PostsController as TranslatablePublicPostsController;
use Javaabu\Mediapicker\Models\Attachment;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\Support\MediaStream;

class PublicPostsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $viewPath = __DIR__ . '/../../Fixtures/views';
        config()->set('view.paths', [$viewPath]);
        $this->app['view']->getFinder()->setPaths([$viewPath]);

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('model');
                $table->uuid('uuid')->nullable()->unique();
                $table->string('collection_name')->nullable();
                $table->string('name');
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->string('disk')->nullable();
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->json('manipulations')->nullable();
                $table->json('custom_properties')->nullable();
                $table->json('generated_conversions')->nullable();
                $table->json('responsive_images')->nullable();
                $table->unsignedInteger('order_column')->nullable();
                $table->json('translations')->nullable();
                $table->string('lang')->nullable();
                $table->boolean('hide_translation')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
                $table->morphs('model');
                $table->string('collection_name')->index();
                $table->unsignedInteger('order_column')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('posts') && ! Schema::hasColumn('posts', 'hide_translation')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->boolean('hide_translation')->default(false);
            });
        }

        Route::get('/admin/media/{media}/edit', fn () => 'edit')->name('admin.media.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function public_index_filters_posts_and_renders_the_default_view(): void
    {
        $type = $this->createPostType('news');
        $matching = $this->createPost($type, [
            'title' => 'Policy Update',
            'slug' => 'policy-update',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $this->createPost($type, [
            'title' => 'Future Policy',
            'slug' => 'future-policy',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->addDay(),
        ]);

        $view = app(PublicPostsController::class)->index('news', Request::create('/news', 'GET', [
            'search' => 'Policy',
            'expiry_status' => 'active',
        ]));

        $this->assertSame('web.post-type.default.index', $view->name());
        $this->assertSame([$matching->id], $view->getData()['posts']->pluck('id')->all());
        $this->assertSame('Policy', $view->getData()['search']);
    }

    #[Test]
    public function public_show_falls_back_to_category_related_posts_and_uses_real_document_attachments(): void
    {
        $categoryType = $this->createCategoryType('news-categories');
        $type = $this->createPostType('news', ['category_type_id' => $categoryType->id]);
        $shared = $this->createCategory($categoryType, 'policy');
        $other = $this->createCategory($categoryType, 'events');

        $post = $this->createPost($type, [
            'title' => 'Current Post',
            'slug' => 'current-post',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDays(2),
        ]);
        $related = $this->createPost($type, [
            'title' => 'Related Post',
            'slug' => 'related-post',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $differentCategory = $this->createPost($type, [
            'title' => 'Different Category',
            'slug' => 'different-category',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subHours(12),
        ]);
        $draft = $this->createPost($type, [
            'title' => 'Draft Related',
            'slug' => 'draft-related',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now()->subHours(10),
        ]);

        $post->categories()->attach($shared->id);
        $related->categories()->attach($shared->id);
        $differentCategory->categories()->attach($other->id);
        $draft->categories()->attach($shared->id);

        $media = $this->attachDocumentToPost($post, 'current-post.pdf', 'documents');

        $view = app(PublicPostsController::class)->show(Request::create('/news/current-post', 'GET'), $post, $type);

        $this->assertSame('web.post-type.default.show', $view->name());
        $this->assertSame([$media->id], $view->getData()['post_documents']->pluck('id')->all());
        $this->assertSame([$related->id], $view->getData()['related_posts']->pluck('id')->all());
    }

    #[Test]
    public function public_download_files_returns_a_zip_stream_for_attached_documents(): void
    {
        $type = $this->createPostType('news');
        $post = $this->createPost($type, [
            'title' => 'Policy Files',
            'slug' => 'policy-files',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $media = $this->attachDocumentToPost($post, 'policy.pdf', 'documents');

        $stream = app(PublicPostsController::class)->downloadFiles(Request::create('/news/policy-files/files', 'GET'), $post, $type);
        $response = $stream->toResponse(Request::create('/news/policy-files/files', 'GET'));

        $this->assertInstanceOf(MediaStream::class, $stream);
        $this->assertSame([$media->id], $stream->getMediaItems()->pluck('id')->all());
        $this->assertSame('attachment; filename="Policy Files.zip"', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function translatable_public_index_uses_locale_scopes_and_hides_hidden_translations(): void
    {
        app()->setLocale('en');
        config()->set('cms.models.post', TranslatablePost::class);

        $type = $this->createPostType('guides');
        $visiblePrimary = $this->createTranslatablePost($type, [
            'title' => 'English Guide',
            'slug' => 'english-guide',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
            'lang' => Languages::EN,
        ]);
        $visibleTranslated = $this->createTranslatablePost($type, [
            'title' => 'Dhivehi Guide',
            'slug' => 'dhivehi-guide',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subHours(20),
            'lang' => Languages::DV,
            'translations' => ['title' => 'Translated Guide'],
            'hide_translation' => false,
        ]);
        $this->createTranslatablePost($type, [
            'title' => 'Hidden Guide',
            'slug' => 'hidden-guide',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subHours(18),
            'lang' => Languages::DV,
            'translations' => ['title' => 'Hidden Translation'],
            'hide_translation' => true,
        ]);

        $view = app(TranslatablePublicPostsController::class)->index('guides', Request::create('/guides', 'GET'));

        $this->assertSame('web.post-type.default.index', $view->name());
        $this->assertEqualsCanonicalizing(
            [$visiblePrimary->id, $visibleTranslated->id],
            $view->getData()['posts']->pluck('id')->all()
        );
    }

    #[Test]
    public function translatable_public_show_uses_the_same_resilient_related_post_logic(): void
    {
        app()->setLocale('en');
        config()->set('cms.models.post', TranslatablePost::class);

        $categoryType = $this->createCategoryType('guide-categories');
        $type = $this->createPostType('guides', ['category_type_id' => $categoryType->id]);
        $shared = $this->createCategory($categoryType, 'translations');

        $post = $this->createTranslatablePost($type, [
            'title' => 'Current Guide',
            'slug' => 'current-guide',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDays(2),
            'lang' => Languages::EN,
        ]);
        $related = $this->createTranslatablePost($type, [
            'title' => 'Related Guide',
            'slug' => 'related-guide',
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
            'lang' => Languages::EN,
        ]);

        $post->categories()->attach($shared->id);
        $related->categories()->attach($shared->id);
        $media = $this->attachDocumentToPost($post, 'guide.pdf', 'documents_translated');

        $view = app(TranslatablePublicPostsController::class)->show(Request::create('/guides/current-guide', 'GET'), $post, $type);

        $this->assertSame('web.post-type.default.show', $view->name());
        $this->assertSame([$media->id], $view->getData()['post_documents']->pluck('id')->all());
        $this->assertSame([$related->id], $view->getData()['related_posts']->pluck('id')->all());
    }

    private function createPostType(string $slug, array $attributes = []): PostType
    {
        $postType = new PostType(array_merge([
            'name' => ucfirst($slug),
            'singular_name' => ucfirst(rtrim($slug, 's')),
            'slug' => $slug,
            'icon' => 'ri-file-line',
        ], $attributes));
        $postType->lang = 'en';
        $postType->save();

        return $postType;
    }

    private function createCategoryType(string $slug): CategoryType
    {
        $categoryType = new CategoryType([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'singular_name' => ucfirst(str_replace('-', ' ', rtrim($slug, 's'))),
            'slug' => $slug,
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        return $categoryType;
    }

    private function createCategory(CategoryType $categoryType, string $slug): Category
    {
        $category = new Category([
            'name' => ucfirst($slug),
            'slug' => $slug,
        ]);
        $category->type_id = $categoryType->id;
        $category->lang = 'en';
        $category->save();

        return $category;
    }

    private function createPost(PostType $postType, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $postType->slug,
            'title' => 'Default Post',
            'slug' => 'post-' . fake()->unique()->numberBetween(1, 999999),
            'content' => 'Default content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));
        $post->lang = $attributes['lang'] ?? 'en';
        $post->save();

        return $post;
    }

    private function createTranslatablePost(PostType $postType, array $attributes = []): TranslatablePost
    {
        $translations = $attributes['translations'] ?? null;
        $hideTranslation = $attributes['hide_translation'] ?? null;
        unset($attributes['translations'], $attributes['hide_translation']);

        $post = new TranslatablePost(array_merge([
            'type' => $postType->slug,
            'title' => 'Default Translatable Post',
            'slug' => 'translatable-post-' . fake()->unique()->numberBetween(1, 999999),
            'content' => 'Default translated content',
            'status' => PostStatus::DRAFT->value,
            'published_at' => now(),
        ], $attributes));
        $post->lang = $attributes['lang'] ?? Languages::EN;
        if ($translations !== null) {
            $post->translations = json_encode($translations);
        }
        if ($hideTranslation !== null) {
            $post->hide_translation = $hideTranslation;
        }
        $post->save();

        return $post;
    }

    private function attachDocumentToPost(Post $post, string $fileName, string $collectionName): Media
    {
        $media = new Media([
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
        ]);
        $media->file_name = $fileName;
        $media->mime_type = 'application/pdf';
        $media->disk = 'public';
        $media->conversions_disk = 'public';
        $media->size = 1024;
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = [];
        $media->responsive_images = [];
        $media->collection_name = $collectionName;
        $media->model_type = User::class;
        $media->model_id = 1;
        $media->save();

        $attachment = new Attachment();
        $attachment->media_id = $media->id;
        $attachment->collection_name = $collectionName;
        $attachment->order_column = 1;
        $attachment->model_type = $post->getMorphClass();
        $attachment->model_id = $post->id;
        $attachment->save();

        return $media;
    }
}
