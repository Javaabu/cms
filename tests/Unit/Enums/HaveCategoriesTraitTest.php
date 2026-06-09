<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Cms\Enums\HaveCategories;
use PHPUnit\Framework\Attributes\Test;

class HaveCategoriesTraitTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            'categorized_test_item' => CategorizedTestItem::class,
        ]);

        if (! Schema::hasTable('categorized_test_items')) {
            Schema::create('categorized_test_items', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }
    }

    #[Test]
    public function it_filters_categories_builds_links_and_finds_similar_models(): void
    {
        $property = new \ReflectionProperty(CategorizedTestItem::class, 'category_type');
        $property->setAccessible(true);
        $property->setValue(null);

        $type = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $type->lang = 'en';
        $type->save();

        $policy = new Category(['name' => 'Policy', 'slug' => 'policy']);
        $policy->type_id = $type->id;
        $policy->lang = 'en';
        $policy->save();

        $sports = new Category(['name' => 'Sports', 'slug' => 'sports']);
        $sports->type_id = $type->id;
        $sports->lang = 'en';
        $sports->save();

        $current = CategorizedTestItem::create([
            'title' => 'Current',
            'slug' => 'current',
            'published_at' => now(),
        ]);
        $related = CategorizedTestItem::create([
            'title' => 'Related',
            'slug' => 'related',
            'published_at' => now(),
        ]);
        $uncategorized = CategorizedTestItem::create([
            'title' => 'Uncategorized',
            'slug' => 'uncategorized',
            'published_at' => now(),
        ]);

        $current->categories()->sync([$policy->id]);
        $related->categories()->sync([$policy->id, $sports->id]);

        $current->load('categories');

        $this->assertSame('news-categories', $current->getCategoryTypeSlug());
        $this->assertSame('categories', $current->getCategoriesRelationName());
        $this->assertSame($type->id, CategorizedTestItem::categoryType()->id);
        $this->assertSame([$policy->id => $policy->depth_name, $sports->id => $sports->depth_name], CategorizedTestItem::categoryList());
        $this->assertSame([$policy->id, $sports->id], CategorizedTestItem::categoriesOf()->pluck('id')->all());
        $this->assertTrue($current->hasAnyCategory($policy->id));
        $this->assertFalse($current->hasAnyCategory([]));
        $this->assertSame([$current->id, $related->id], CategorizedTestItem::query()->belongsToCategory($policy->id)->pluck('categorized_test_items.id')->all());
        $this->assertSame([$uncategorized->id], CategorizedTestItem::query()->doesNotBelongToAnyCategory()->pluck('categorized_test_items.id')->all());
        $this->assertSame([$current->id, $related->id, $related->id], CategorizedTestItem::query()->joinCategoriesPivot()->orderBy('categorized_test_items.id')->pluck('categorized_test_items.id')->all());
        $this->assertSame([$related->id], $current->similarByCategory()->pluck('categorized_test_items.id')->all());
        $this->assertStringContainsString('Policy', $current->category_links);
    }

    #[Test]
    public function it_removes_category_pivots_when_the_model_is_deleted(): void
    {
        $type = new CategoryType([
            'name' => 'Delete Categories',
            'singular_name' => 'Delete Category',
            'slug' => 'delete-categories',
        ]);
        $type->lang = 'en';
        $type->save();

        $category = new Category(['name' => 'Policy', 'slug' => 'policy']);
        $category->type_id = $type->id;
        $category->lang = 'en';
        $category->save();

        $item = CategorizedTestItem::create([
            'title' => 'Disposable',
            'slug' => 'disposable',
            'published_at' => now(),
        ]);
        $item->categories()->sync([$category->id]);

        $this->assertDatabaseHas('category_model', ['model_id' => $item->id]);

        $item->delete();

        $this->assertDatabaseMissing('category_model', ['model_id' => $item->id]);
    }
}

class CategorizedTestItem extends \Illuminate\Database\Eloquent\Model
{
    use HaveCategories;

    protected $table = 'categorized_test_items';
    protected $fillable = ['title', 'slug', 'published_at'];
    protected $casts = ['published_at' => 'datetime'];

    public function getCategoryTypeSlug()
    {
        return 'news-categories';
    }
}
