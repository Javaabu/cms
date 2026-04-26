<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Cms\Models\Category;
use Illuminate\Support\Arr;
use Javaabu\Cms\Models\CategoryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HaveCategories
{

    /**
     * The category type
     *
     * @var CategoryType
     */
    protected static $category_type;

    /**
     * Boot function from laravel.
     */
    public static function bootHaveCategories()
    {
        static::deleted(function ($model) {
            // if the model doesn't support soft deletes
            // or if it is being force deleted
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                DB::table('category_model')
                  ->where('model_id', $model->id)
                  ->where('model_type', $model->type)
                  ->delete();
            }
        });
    }

    /**
     * Get the category list
     *
     * @return array
     */
    public static function categoryList()
    {
        return Category::categoryList(static::categoryType()->id);
    }

    /**
     * Get the category type
     *
     * @return CategoryType
     */
    public static function categoryType()
    {
        if (empty(static::$category_type)) {
            static::$category_type = CategoryType::whereSlug(
                with(new static())->getCategoryTypeSlug())
                                                 ->first();
        }

        return static::$category_type;
    }

    /**
     * Get the name of the category type slug
     *
     * @return string
     */
    public function getCategoryTypeSlug()
    {
        return 'category';
    }

    /**
     * Get the category list
     *
     * @return array
     */
    public static function categoriesOf()
    {
        return Category::categoriesOf(static::categoryType()->id);
    }

    /**
     * Belongs to categories
     *
     * @return morphToMany
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'model', 'category_model');
    }

    /**
     * Belongs to category
     *
     * @param                    $query
     * @param Category | array $category_ids
     * @param string $relation
     * @return mixed
     */
    public function scopeBelongsToCategory($query, $category_ids, $relation = '')
    {
        $category_ids = Arr::wrap($category_ids);

        if (empty($relation)) {
            $relation = $this->getCategoriesRelationName();
        }

        if (empty($category_ids)) {
            return $query;
        }

        // Get goods
        return $query->whereHas($relation, function ($query) use ($category_ids) {
            return $query->whereIn('category_id', $category_ids);
        });
    }

    /**
     * Get the categories relation
     *
     * @return string
     */
    public function getCategoriesRelationName(): string
    {
        return 'categories';
    }

    /**
     * Belongs to category
     *
     * @param                    $query
     * @param string $relation
     * @return mixed
     */
    public function scopeDoesNotBelongToAnyCategory($query, $relation = '')
    {
        if (empty($relation)) {
            $relation = $this->getCategoriesRelationName();
        }

        // Get goods
        return $query->doesntHave($relation);
    }

    /**
     * Has any category
     *
     * @param Category | array $category_ids
     * @param string $relation
     * @return boolean
     */
    public function hasAnyCategory($category_ids, $relation = '')
    {
        $category_ids = Arr::wrap($category_ids);

        if (empty($relation)) {
            $relation = $this->getCategoriesRelationName();
        }

        if (empty($category_ids)) {
            return false;
        }

        return $this->{$relation}
                ->plucK('category_id')
                ->intersect(collect($category_ids))
                ->count() > 0;
    }

    /**
     * Join with categories
     *
     * @param $query
     * @return mixed
     */
    public function scopeJoinCategoriesPivot($query)
    {
        $model = $this;
        return $query->join('category_model as c_m', function ($join) use ($model) {
            $join->on($model->getTable() . '.id', '=', 'c_m.model_id')
                 ->where('c_m.model_type', '=', $model->getMorphClass());
        });
    }

    /**
     * Get similar by category
     *
     * @param string $relation
     * @return Builder
     */
    public function similarByCategory($relation = '')
    {
        if (empty($relation)) {
            $relation = $this->getCategoriesRelationName();
        }

        return static::where('id', '!=', $this->id)
                     ->similarToCategories(
                         $this->{$relation}->pluck('category_id')->all()
                     );
    }

    /**
     * Get similar
     *
     * @param         $query
     * @param array $categories
     * @return Builder
     * @internal param string $relation
     */
    public function scopeSimilarToCategories($query, $categories = [])
    {
        return $query->joinCategoriesPivot()
                     ->selectRaw('count(c_m.category_id) as similarity, ' . $this->getTable() . '.*')
                     ->groupBy('c_m.model_id')
                     ->whereIn('c_m.category_id', $categories);
    }

    /**
     * Get the category links attribute
     *
     * @return string
     */
    public function getCategoryLinksAttribute()
    {
        return $this->getCategoryLinks($this->getCategoriesRelationName());
    }

    /**
     * Get the category links attribute
     *
     * @param string $relation
     * @return string
     */
    public function getCategoryLinks($relation = 'categories')
    {
        $html = '';

        foreach ($this->{$relation} as $category) {
            $html .= $category->admin_link . ', ';
        }

        return rtrim($html, ', ');
    }
}
