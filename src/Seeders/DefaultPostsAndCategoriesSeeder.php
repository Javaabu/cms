<?php

namespace Javaabu\Cms\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;

class DefaultPostsAndCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedCategoryTypes();
        $this->seedPostTypes();
        $this->seedCategories();
    }

    /**
     * Seed default category types
     *
     * @return void
     */
    protected function seedCategoryTypes()
    {
        $categoryTypes = config('cms.default_category_types', []);

        foreach ($categoryTypes as $slug => $data) {
            $type = CategoryType::whereSlug($slug)->first();

            if (!$type) {
                $type = new CategoryType();
            }

            $type->name = $data['name'];
            $type->singular_name = $data['singular_name'];
            $type->slug = $slug;
            $type->lang = Languages::EN;
            $type->save();
        }
    }

    /**
     * Seed default post types
     *
     * @return void
     */
    protected function seedPostTypes()
    {
        $postTypes = config('cms.default_post_types', []);
        $count = 0;

        foreach ($postTypes as $slug => $data) {
            $type = PostType::whereSlug($slug)->first();

            if (!$type) {
                $type = new PostType();
            }

            $type->name = $data['name'];
            $type->singular_name = $data['singular_name'];
            $type->slug = $slug;
            $type->lang = Languages::EN;
            $type->icon = $data['icon'] ?? null;

            // Associate category type if specified
            if (isset($data['category_type'])) {
                $categoryType = CategoryType::whereSlug($data['category_type'])->first();
                $type->categoryType()->associate($categoryType);
            }

            $type->features = $data['features'] ?? [];
            $type->description = $data['description'] ?? null;
            $type->og_description = $data['og_description'] ?? null;
            $type->order_column = $count;

            $type->save();

            $count++;
        }
    }

    /**
     * Seed default categories
     *
     * @return void
     */
    protected function seedCategories()
    {
        $categories = config('cms.default_categories', []);

        foreach ($categories as $categoryTypeSlug => $categoryDatas) {
            $categoryType = CategoryType::whereSlug($categoryTypeSlug)->first();

            if (!$categoryType) {
                continue;
            }

            foreach ($categoryDatas as $categoryData) {
                $slug = $categoryData['slug'] ?? Str::slug($categoryData['name']);

                $category = Category::whereSlug($slug)
                    ->whereTypeId($categoryType->id)
                    ->first();

                if (!$category) {
                    $category = new Category(compact('slug'));
                    $category->type_id = $categoryType->id;
                }

                $category->name = $categoryData['name'];
                $category->lang = Languages::EN;

                if (isset($categoryData['description'])) {
                    $category->description = $categoryData['description'];
                }

                if (isset($categoryData['icon'])) {
                    $category->icon = $categoryData['icon'];
                }

                if (isset($categoryData['order_column'])) {
                    $category->order_column = $categoryData['order_column'];
                }

                $category->save();
            }
        }
    }

    /**
     * Seed defaults - static method for easy calling from application seeders
     *
     * @return void
     */
    public static function seedDefaults(): void
    {
        (new static)->run();
    }
}
