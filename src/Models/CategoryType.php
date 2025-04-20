<?php

namespace Javaabu\Cms\Models;

use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Traits\HasSlug;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Helpers\AdminModel\AdminModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;
class CategoryType extends Model implements AdminModel, Translatable
{
    use IsAdminModel;
    use HasSlug;
    use IsJsonTranslatable;

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
    protected $fillable = ['name'];

    protected $searchable = ['name'];

    public function getTranslatables(): array
    {
        if (! config('cms.should_translate')) {
            return [];
        }
        return [
            'name',
            'singular_name',
        ];
    }

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return '#';
    }

    /**
     * Get the permission slug
     * @return string
     */
    public function getPermissionSlugAttribute(): string
    {
        return Str::slug($this->slug, '_');
    }

    /**
     * Get the route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

//    /**
//     * A category type has many categories
//     *
//     * @return HasMany
//     */
//    public function categories(): HasMany
//    {
//        return $this->hasMany(Category::class, 'type_id');
//    }

    /**
     * Slugify the value
     *
     * @param $value
     */
    public function setSlugAttribute($value): void
    {
        // convert to slug
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * get title
     */
    public function getTitleAttribute()
    {
        return $this->name;
    }

    /**
     * Relation to post type
     * @return HasOne
     */
    public function postType(): HasOne
    {
        return $this->hasOne(PostType::class);
    }
}
