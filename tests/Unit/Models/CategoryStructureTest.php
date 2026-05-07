<?php

namespace Javaabu\Cms\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryStructureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_flattened_category_lists_in_nested_order_and_can_skip_a_branch(): void
    {
        $type = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $type->lang = 'en';
        $type->save();

        $parent = new Category([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);
        $parent->type_id = $type->id;
        $parent->lang = 'en';
        $parent->save();

        $child = new Category([
            'name' => 'Child',
            'slug' => 'child',
        ]);
        $child->type_id = $type->id;
        $child->lang = 'en';
        $child->appendToNode($parent)->save();

        Category::fixTree();

        $this->assertSame([
            $parent->id => 'Parent',
            $child->id => '― Child',
        ], Category::categoryList($type->id));

        $this->assertSame([], Category::categoryList($type->id, $parent));
    }

    #[Test]
    public function it_exposes_tree_and_display_accessors(): void
    {
        $type = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $type->lang = 'en';
        $type->save();

        $parent = new Category([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);
        $parent->type_id = $type->id;
        $parent->lang = 'en';
        $parent->save();

        $child = new Category([
            'name' => 'Child',
            'slug' => 'child',
        ]);
        $child->type_id = $type->id;
        $child->lang = 'en';
        $child->appendToNode($parent)->save();

        Category::fixTree();
        $parent->refresh();
        $child->refresh();

        $this->assertFalse($parent->has_parent);
        $this->assertTrue($parent->has_children);
        $this->assertTrue($child->has_parent);
        $this->assertFalse($child->has_children);
        $this->assertSame('Child', $child->title);
        $this->assertSame($child->depth_name, $child->admin_link_name);
        $category = new Category();
        $category->order_column = null;

        $this->assertSame(0, $category->order_column);
    }
}
