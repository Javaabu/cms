<?php

namespace Javaabu\Cms\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Tests\Concerns\CreatesV1Tables;
use Javaabu\Cms\Tests\Concerns\InteractsWithCms;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Upgrade\V1Tables;
use Javaabu\PostTypes\Facades\PostTypes;
use PHPUnit\Framework\Attributes\Test;

class ImportV1Test extends TestCase
{
    use CreatesV1Tables;
    use InteractsWithCms;

    protected function seedV1(): void
    {
        $this->createV1Tables('cms_v1_');

        $now = Carbon::parse('2024-05-01 10:00:00');

        DB::table('cms_v1_category_types')->insert(['id' => 1, 'name' => 'News Categories', 'singular_name' => 'News Category', 'slug' => 'news-categories']);
        DB::table('cms_v1_categories')->insert([
            ['id' => 1, 'type_id' => 1, 'name' => 'ރާއްޖެ', 'slug' => 'local', 'order_column' => 1, '_lft' => 1, '_rgt' => 4, 'parent_id' => null, 'translations' => json_encode(['en' => ['name' => 'Local']]), 'lang' => 'dv'],
            ['id' => 2, 'type_id' => 1, 'name' => 'މާލެ', 'slug' => 'male', 'order_column' => 0, '_lft' => 2, '_rgt' => 3, 'parent_id' => 1, 'translations' => null, 'lang' => null],
        ]);
        DB::table('cms_v1_post_types')->insert([
            ['id' => 1, 'name' => 'News', 'singular_name' => 'News Article', 'slug' => 'news', 'icon' => 'zmdi-assignment', 'order_column' => 1],
            ['id' => 2, 'name' => 'Pages', 'singular_name' => 'Page', 'slug' => 'pages', 'icon' => 'zmdi-file', 'order_column' => 2],
            ['id' => 3, 'name' => 'Tenders', 'singular_name' => 'Tender', 'slug' => 'tenders', 'icon' => 'zmdi-assignment', 'order_column' => 3],
        ]);

        $content = json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'ޚަބަރުގެ <b>ތަފްސީލު</b>']]]]);

        DB::table('cms_v1_posts')->insert([
            ['id' => 7, 'type' => 'news', 'title' => 'ރާއްޖޭގެ ޚަބަރު', 'slug' => 'local-news', 'content' => $content, 'excerpt' => 'ޚުލާސާ', 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null, 'document_no' => 'DOC/2024/1', 'translations' => json_encode(['en' => ['title' => 'Local news', 'content' => $content]]), 'lang' => 'dv'],
            ['id' => 8, 'type' => 'news', 'title' => str_repeat('ހ', 300), 'slug' => 'long', 'content' => null, 'excerpt' => null, 'status' => 'rejected', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null, 'document_no' => null, 'translations' => null, 'lang' => null],
            ['id' => 9, 'type' => 'pages', 'title' => 'ތަޢާރަފު', 'slug' => 'about', 'content' => '<p>HTML</p>', 'excerpt' => null, 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => $now, 'document_no' => null, 'translations' => null, 'lang' => null],
            ['id' => 10, 'type' => 'tenders', 'title' => 'ބީލަން', 'slug' => 'tender', 'content' => null, 'excerpt' => null, 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null, 'document_no' => null, 'translations' => null, 'lang' => null],
        ]);

        DB::table('cms_v1_category_model')->insert([
            ['model_type' => 'post', 'model_id' => 7, 'category_id' => 2],
            ['model_type' => 'post', 'model_id' => 10, 'category_id' => 1],
        ]);
        DB::table('cms_v1_tags')->insert(['id' => 1, 'name' => 'Sports News']);
        DB::table('cms_v1_tag_model')->insert(['model_type' => 'post', 'model_id' => 7, 'tag_id' => 1]);

        DB::table('media')->insert([
            'id' => 1, 'model_type' => 'admin', 'model_id' => 1, 'uuid' => 'a', 'collection_name' => 'mediapicker', 'name' => 'photo', 'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg', 'disk' => 'public', 'conversions_disk' => 'public', 'size' => 10, 'manipulations' => '[]', 'custom_properties' => '[]',
            'generated_conversions' => '[]', 'responsive_images' => '[]', 'order_column' => 1,
        ]);
        DB::table('attachments')->insert(['media_id' => 1, 'model_type' => 'post', 'model_id' => 7, 'collection_name' => 'featured_image']);
    }

    #[Test]
    public function it_imports_v1_posts_terms_tags_meta_and_attachments(): void
    {
        $this->seedV1();

        $this->artisan('cms:import-v1')
            ->expectsOutputToContain('tenders')
            ->assertSuccessful();

        $model = PostTypes::postModel();
        $news = $model::query()->find(7);

        $this->assertSame('news', $news->type);
        $this->assertSame('ރާއްޖޭގެ ޚަބަރު', (string) $news->getRawOriginal('title'));
        $this->assertSame('local-news', $news->slug);
        $this->assertTrue($news->isPublished());
        $this->assertStringContainsString('<strong>ތަފްސީލު</strong>', $news->contentDocument()->toHtml());
        $this->assertSame('DOC/2024/1', $news->getMeta('document_no'));
        $this->assertSame(['މާލެ'], $news->termsOf('categories')->pluck('name')->map(fn ($name) => (string) $name)->all());
        $this->assertSame(['sports-news'], $news->termsOf('tags')->pluck('slug')->all());
        $this->assertSame('Local news', json_decode($news->getRawOriginal('translations'), true)['en']['title']);
        $this->assertSame('doc', json_decode($news->getRawOriginal('translations'), true)['en']['content']['type']);

        $male = PostTypes::termModel()::query()->where('slug', 'male')->first();
        $local = PostTypes::termModel()::query()->where('slug', 'local')->first();

        $this->assertSame($local->id, $male->parent_id);
        $this->assertSame('categories', $local->taxonomy);

        $this->assertSame('draft', $model::query()->find(8)->status);
        $this->assertSame(255, mb_strlen((string) $model::query()->find(8)->getRawOriginal('title')));

        $page = $model::withTrashed()->find(9);

        $this->assertSame('page', $page->type);
        $this->assertTrue($page->trashed());
        $this->assertNull($model::withTrashed()->find(10));

        $this->assertSame((new $model)->getMorphClass(), DB::table('attachments')->value('model_type'));
        $this->assertSame(7, (int) DB::table('attachments')->value('model_id'));
    }

    #[Test]
    public function v1_types_can_be_mapped_to_v2_types(): void
    {
        $this->seedV1();

        $this->artisan('cms:import-v1', ['--type' => ['tenders:news'], '--taxonomy' => ['news-categories:tags']])->assertSuccessful();

        $model = PostTypes::postModel();

        $this->assertSame('news', $model::query()->find(10)->type);
        $this->assertSame('tags', PostTypes::termModel()::query()->where('slug', 'local')->value('taxonomy'));
    }

    #[Test]
    public function a_dry_run_saves_nothing(): void
    {
        $this->seedV1();

        $this->artisan('cms:import-v1', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(0, PostTypes::postModel()::withTrashed()->count());
    }

    #[Test]
    public function it_refuses_when_there_are_posts_already_unless_forced(): void
    {
        $this->seedV1();
        $this->createPost('news', ['slug' => 'local-news']);

        $this->artisan('cms:import-v1')->assertFailed();

        $this->artisan('cms:import-v1', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, PostTypes::postModel()::query()->where('slug', 'local-news-2')->count());
    }

    #[Test]
    public function without_v1_tables_it_says_so(): void
    {
        $this->artisan('cms:import-v1')
            ->expectsOutputToContain('No v1 content found')
            ->assertFailed();
    }

    #[Test]
    public function the_migration_renames_v1_tables_out_of_the_way(): void
    {
        Schema::drop('post_term');
        Schema::drop('posts');

        $this->createV1Tables();

        $this->assertTrue(V1Tables::isV1PostsTable());

        $renamed = V1Tables::rename();

        $this->assertSame('cms_v1_posts', $renamed['posts']);
        $this->assertFalse(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('cms_v1_categories'));
        $this->assertTrue(V1Tables::hasRenamed());
        $this->assertSame([], V1Tables::rename());
    }

    #[Test]
    public function the_migration_leaves_new_installs_alone(): void
    {
        $this->assertFalse(V1Tables::isV1PostsTable());
        $this->assertSame([], V1Tables::rename());
        $this->assertTrue(Schema::hasTable('posts'));
    }
}
