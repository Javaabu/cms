<?php

namespace Javaabu\Cms\Tests\Unit\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatableSchema;
use Javaabu\Cms\Helpers\PostTypeSchema;
use Javaabu\Cms\Support\Faker\EnglishContentBlock;
use Javaabu\Cms\Support\Faker\Factory\ContentBlockFactory;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SupportHelpersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function english_content_block_builds_full_editor_js_payload(): void
    {
        $payload = json_decode((new EnglishContentBlock(fake()))->get(), true);

        $this->assertIsInt($payload['time']);
        $this->assertSame('2.19.3', $payload['version']);
        $this->assertCount(16, $payload['blocks']);
        $this->assertSame(['header', 'header', 'header', 'header', 'header', 'header'], array_column(array_slice($payload['blocks'], 0, 6), 'type'));
        $this->assertSame('paragraph', $payload['blocks'][6]['type']);
        $this->assertSame('ordered', $payload['blocks'][7]['data']['style']);
        $this->assertSame('unordered', $payload['blocks'][8]['data']['style']);
        $this->assertSame('quote', $payload['blocks'][9]['type']);
        $this->assertSame('table', $payload['blocks'][10]['type']);
        $this->assertSame(
            ['top', 'left', 'right', 'divider'],
            array_map(fn (array $block) => $block['data']['imagePosition'], array_slice($payload['blocks'], 11, 4))
        );
        $this->assertSame('action_card', $payload['blocks'][15]['type']);
    }

    #[Test]
    public function english_content_block_builds_lite_and_even_lite_payloads(): void
    {
        $lite = json_decode((new EnglishContentBlock(fake(), true))->get(), true);

        $this->assertSame(['paragraph', 'quote', 'media_image', 'media_image'], array_column($lite['blocks'], 'type'));
        $this->assertSame(
            ['left', 'right'],
            array_map(fn (array $block) => $block['data']['imagePosition'], array_slice($lite['blocks'], 2))
        );

        $even_lite = json_decode((new EnglishContentBlock(fake(), true, true))->get(), true);

        $this->assertSame('paragraph', $even_lite['blocks']['type']);
        $this->assertArrayHasKey('text', $even_lite['blocks']['data']);
    }

    #[Test]
    public function content_block_factory_returns_full_and_lite_json_payloads(): void
    {
        $factory = new class extends ContentBlockFactory {
            protected $model = FactoryBackedModel::class;

            public function definition(): array
            {
                return [];
            }
        };

        $content = json_decode($factory->getContentBlock(), true);
        $lite = json_decode($factory->getLiteContentBlock(), true);

        $this->assertSame(['paragraph', 'quote', 'media_image', 'media_image'], array_column($content['blocks'], 'type'));
        $this->assertSame('paragraph', $lite['blocks']['type']);
    }

    #[Test]
    public function post_type_schema_adds_expected_columns_and_skips_fulltext_during_tests(): void
    {
        Schema::create('schema_posts', function (Blueprint $table) {
            PostTypeSchema::columns($table, 'headline');
        });

        $this->assertTrue(Schema::hasColumns('schema_posts', [
            'id',
            'headline',
            'slug',
            'content',
            'excerpt',
            'menu_order',
            'status',
            'published_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));

        PostTypeSchema::index('schema_posts', 'headline');

        Schema::create('schema_posts_without_name', function (Blueprint $table) {
            PostTypeSchema::columns($table, '');
        });

        $this->assertTrue(Schema::hasColumn('schema_posts_without_name', 'content'));
        $this->assertFalse(Schema::hasColumn('schema_posts_without_name', 'slug'));
    }

    #[Test]
    public function json_translatable_schema_adds_translation_columns_for_testing_databases(): void
    {
        Schema::create('schema_translations', function (Blueprint $table) {
            $table->id();
            JsonTranslatableSchema::columns($table);
        });

        $this->assertTrue(Schema::hasColumns('schema_translations', [
            'translations',
            'lang',
            'hide_translation',
        ]));
    }
}

class FactoryBackedModel extends \Illuminate\Database\Eloquent\Model
{
}
