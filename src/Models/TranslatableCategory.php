<?php

namespace Javaabu\Cms\Models;

use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatable;
use Javaabu\Cms\Enums\JsonTranslatable\IsJsonTranslatable;

class TranslatableCategory extends Category implements JsonTranslatable
{
    use IsJsonTranslatable;

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(['lang']);
        $this->mergeCasts([
            'translations' => 'array',
            'lang' => \Javaabu\Cms\Enums\Languages::class,
        ]);
        parent::__construct($attributes);
    }

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = [
        'name',
    ];

    /**
     * A search scope
     *
     * @param $query
     * @param $search
     * @return
     */
    public function scopeSearch($query, $search, $locale = null): mixed
    {
        return $query->translationsSearch('name', $search, $locale);
    }

    public function getPermalinkAttribute(): string
    {
        $category_type = $this->type;

        if ($category_type->slug == 'department-categories') {
            $route_name = 'web.departments.index';
        } elseif ($category_type->slug == 'staff-categories') {
            $route_name = 'web.staff-directory.index';
        } else {
            $route_name = "web.posts.index.{$category_type->postType->slug}";
        }

        $locale = app()->getLocale();

        // is post has current locale,
        if (! $this->hasTranslations($locale)) {
            $locale = \Javaabu\Cms\Enums\Languages::getOppositeLocale($locale);
        }

        $route = translate_route($route_name, [], true, $locale);
        return add_query_arg(['category' => $this->id], $route);
    }
}
