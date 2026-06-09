<?php

namespace Javaabu\Cms\Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Models\Tag;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Traits\IsTaggable;
use PHPUnit\Framework\Attributes\Test;

class IsTaggableTraitTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            'taggable_test_item' => TaggableTestItem::class,
        ]);

        if (! Schema::hasTable('taggable_test_items')) {
            Schema::create('taggable_test_items', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable();
                $table->string('title');
                $table->string('slug');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        Route::get('/admin/tags/{tag}', fn (Tag $tag) => $tag->id)->name('admin.tags.show');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function it_filters_by_tags_computes_similarity_and_formats_tag_links(): void
    {
        $shared = $this->createTag('Shared Topic');
        $secondary = $this->createTag('Second Topic');
        $other = $this->createTag('Other Topic');

        $current = TaggableTestItem::create([
            'type' => 'taggable_test_item',
            'title' => 'Current',
            'slug' => 'current',
            'published_at' => now()->subDay(),
        ]);
        $related = TaggableTestItem::create([
            'type' => 'taggable_test_item',
            'title' => 'Related',
            'slug' => 'related',
            'published_at' => now(),
        ]);
        $unrelated = TaggableTestItem::create([
            'type' => 'taggable_test_item',
            'title' => 'Unrelated',
            'slug' => 'unrelated',
            'published_at' => now(),
        ]);

        $current->tagWords()->sync([$shared->id, $secondary->id]);
        $related->tagWords()->sync([$shared->id]);
        $unrelated->tagWords()->sync([$other->id]);

        $current->load('tagWords');

        $this->assertTrue($current->hasAnyTag($shared->id));
        $this->assertTrue($current->hasAnyTag([$shared->id, $other->id]));
        $this->assertFalse($current->hasAnyTag([]));
        $this->assertFalse($current->hasAnyTag($other->id));
        $this->assertSame([$current->id, $related->id], TaggableTestItem::query()->belongsToTag($shared->id)->pluck('taggable_test_items.id')->all());
        $this->assertSame(
            [$current->id, $current->id, $related->id, $unrelated->id],
            TaggableTestItem::query()->joinTagsPivot()->orderBy('taggable_test_items.id')->pluck('taggable_test_items.id')->all()
        );
        $this->assertSame([$related->id], $current->similarByTag()->pluck('taggable_test_items.id')->all());
        $this->assertSame([$related->id], TaggableTestItem::query()->similarToTags($current)->pluck('taggable_test_items.id')->all());
        $this->assertStringContainsString('Shared Topic', $current->tag_links);
        $this->assertStringContainsString('Second Topic', $current->getTagLinks());
    }

    #[Test]
    public function it_syncs_sanitized_tag_names_and_removes_pivot_rows_on_delete(): void
    {
        $existing = $this->createTag('existing tag');

        $item = TaggableTestItem::create([
            'type' => 'taggable_test_item',
            'title' => 'Syncable',
            'slug' => 'syncable',
            'published_at' => now(),
        ]);

        $another = $this->createTag('brand new tag');

        $item->syncTags(['Existing   Tag', ' Brand New Tag ']);
        $item->load('tagWords');

        $this->assertEqualsCanonicalizing(
            ['existing tag', 'brand new tag'],
            $item->tagWords->pluck('name')->all()
        );
        $this->assertSame([$existing->id, $another->id], $item->tagWords->pluck('id')->sort()->values()->all());

        $this->assertSame($item->id, \DB::table('tag_model')->value('model_id'));

        $item->delete();

        $this->assertDatabaseMissing('tag_model', ['model_id' => $item->id]);
        $this->assertDatabaseHas('tags', ['id' => $existing->id, 'name' => 'existing tag']);
    }

    private function createTag(string $name): Tag
    {
        $tag = new Tag(['name' => $name]);
        $tag->lang = 'en';
        $tag->save();

        return $tag;
    }
}

class TaggableTestItem extends \Illuminate\Database\Eloquent\Model
{
    use IsTaggable;

    protected $table = 'taggable_test_items';
    protected $fillable = ['type', 'title', 'slug', 'published_at'];
    protected $casts = ['published_at' => 'datetime'];
}
