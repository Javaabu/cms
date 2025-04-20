<?php

namespace Javaabu\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Helpers\Media\AllowedMimeTypes;
use Javaabu\Helpers\Traits\HasSlug;
use Javaabu\MenuBuilder\Traits\HasIcon;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model implements AdminModel, Translatable
{
    use IsAdminModel;
    use HasIcon;
    use IsJsonTranslatable;
    use HasSlug;
    use NodeTrait;

//    protected static string $icons_class = FontAwesomeIcons::class;

    /**
     * The attributes that would be logged
     *
     * @var array
     */
    protected static array $logAttributes = ['*'];

    /**
     * Changes to these attributes only will not trigger a log
     *
     * @var array
     */
    protected static array $ignoreChangedAttributes = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that are cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'order_column' => 'integer',
        'translations' => 'array',
        'lang' => Languages::class,
    ];

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchable = [
        'name', 'icon', 'order_column', 'statistic_type'
    ];

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return translate_route('admin.categories.edit', [$this->type, $this]);
    }

    /**
     * A category belongs to a category type
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class);
    }

    /**
     * Get a scoped query
     *
     * @param $type_id
     * @return \Kalnoy\Nestedset\QueryBuilder
     */
    public static function scopedQuery($type_id)
    {
        if ($type_id instanceof CategoryType) {
            $type_id = $type_id->id;
        }

        return self::scoped([
            'type_id' => $type_id,
        ]);
    }

    /**
     * Returns a flattened list of categories
     *
     * @param        $type_id
     * @param null $skip_id id to not return
     * @return array
     */
    public static function categoryList($type_id, $skip_id = null)
    {
        return self::categoriesOf($type_id, $skip_id)->pluck('depth_name', 'id')->all();

        /*if ($skip_id instanceof Category) {
            $skip_id = $skip_id->id;
        }

        $categories = Category::where('type_id', $type_id)
                              ->withDepth()
                              ->defaultOrder()
                              ->orderBy('name');

        if ($skip_id) {
            $categories = $categories
                ->where('id', '!=', $skip_id)
                ->whereNotDescendantOf($skip_id);
        }

        return $categories->get()->pluck('depth_name', 'id')->all();*/
    }

    /**
     * Returns a flattened list of categories
     *
     * @param        $type_id
     * @param null $skip_id id to not return
     * @return array
     */
    public static function categoriesOf($type_id, $skip_id = null): array
    {
        if ($skip_id instanceof Category) {
            $skip_id = $skip_id->id;
        }

        $categories = Category::where('type_id', $type_id)
            ->withDepth()
            ->defaultOrder()
            ->orderBy('name');

        if ($skip_id) {
            $categories = $categories
                ->where('id', '!=', $skip_id)
                ->whereNotDescendantOf($skip_id);
        }

        return $categories->get();
    }

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

    /**
     * Scope to get a particular type
     *
     * @param Builder $query
     * @param           $type
     * @return Builder
     */
    public function scopeCategoryType(Builder $query, $type): Builder
    {
        return $query->whereHas('type', function ($query) use ($type) {
            return $query->whereSlug($type);
        });
    }

    /**
     * Check if slug is unique
     *
     * @param string $value
     * @return bool
     */
    public function isUniqueSlug($value): bool
    {
        //check if slug exists
        $id_key = $this->getKeyName();
        $count = static::whereSlug($value)
            ->whereTypeId($this->type_id);

        if ($id = $this->{$id_key}) {
            $count->where($id_key, '!=', $id);
        }

        return ! $count->exists();
    }

    /**
     * Define attachment collections
     */
    public function registerAttachmentCollections()
    {
        $this->addAttachmentCollection('featured_image')
            ->singleFile()
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType, 'image');
            });
    }

//    /**
//     * Register image conversions
//     *
//     * @param Media|null $media
//     */
//    public function registerAttachmentConversions(Media $media = null)
//    {
//        $this->addAttachmentConversion('shareable_image')
//            ->width(1200)
//            ->height(630)
//            ->fit(Fit::Crop, 1200, 630)
//            ->performOnCollections('featured_image');
//
//        $this->addAttachmentConversion('header_image')
//            ->width(1920)
//            ->height(300)
//            ->fit(Fit::Crop, 1920, 300)
//            ->performOnCollections('featured_image');
//    }

//    public function getPermalinkAttribute(): string
//    {
//        // TODO: Implement getPermalinkAttribute() method.
//        $category_type = $this->type;
//
//        if ($category_type->slug == 'department-categories') {
//            $route_name = 'web.departments.index';
//        } elseif ($category_type->slug == 'staff-categories') {
//            $route_name = 'web.staff-directory.index';
//        } else {
//            $route_name = "web.posts.index.{$category_type->postType->slug}";
//        }
//
//        $locale = app()->getLocale();
//
//        // is post has current locale,
//        if (! $this->hasTranslations($locale)) {
//            $locale = Languages::getOppositeLocale($locale);
//        }
//
//        $route = translate_route($route_name, [], true, $locale);
//        return add_query_arg(['category' => $this->id], $route);
//    }

    public function getOgExcerptAttribute()
    {
        return '';
    }

    /**
     * get title
     */
    public function getTitleAttribute()
    {
        return $this->name;
    }

    /**
     * Checks if the category has a parent
     */
    public function getHasParentAttribute()
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Checks if the category have any children
     */
    public function getHasChildrenAttribute()
    {
        return $this->children()->exists();
    }

    /**
     * Get depth name
     */
    public function getDepthNameAttribute()
    {
        return str_repeat('― ', $this->depth) . $this->name;
    }

    public function getAdminLinkNameAttribute(): string
    {
        return $this->depth_name;
    }

    /**
     * set order column value
     */
    public function setOrderColumnAttribute($value)
    {
        return $this->attributes['order_column'] = $value ?? 0;
    }

    /**
     * Returns the url
     *
     * @param string $action
     * @param string|null $locale
     * @param string $namespace
     * @return string
     */
    public function url(string $action = 'show', string $locale = null, string $namespace = 'admin'): string
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }

        $controller = Str::lower(Str::plural(Str::kebab(class_basename(get_class($this)))));
        $controller_action = $namespace . '.' . $controller . '.' . $action;

        $params = [];

        $params[] = $locale ?: app()->getLocale();
        $params[] = $this->type;

        if (! in_array($action, ['index', 'store', 'create', 'trash'])) {
            $params[] = $this->id;
        }

        return URL::route($controller_action, $params);
    }

    /**
     * User visible
     *
     * @param                $query
     * @param CategoryType $type
     * @return mixed
     */
    public function scopeUserVisible($query, CategoryType $type)
    {
        $user = auth()->user();

        if ($user && $user->can('create', $type)) {
            //can view all
            return $query;
        }

        // everyone can view published
        return $query;
    }

    /**
     * Finds posts related to this category by checking the pivot table
     * where posts and categories are linked.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'model', 'category_model');
    }

    /**
     * Get link to post admin link
     */
    public function getPostAdminLinkAttribute(): string
    {
        if ($this->type->slug == 'staff-categories') {
            return translate_route('admin.staff.index');
        }

        return translate_route('admin.posts.index', $this->type->postType);
    }


    /**
     * get projects url
     */
    public function getProjectsUrlAttribute()
    {
        return route('web.projects.index', ['language' => app()->getLocale(), 'category' => $this->id]);
    }

    /**
     * get map link
     */
    public function getMapLinkAttribute(): string
    {
        return route('web.map', ['language' => app()->getLocale(), 'zoom' => 7, 'category' => $this->slug]);
    }

    public function getTranslatables(): array
    {
        if (! config('cms.should_translate')) {
            return [];
        }
        return [
            'name',
        ];
    }
}
