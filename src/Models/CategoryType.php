<?php

namespace Javaabu\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CategoryType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'singular_name',
        'slug',
    ];

    /**
     * Get the route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * A category type has many categories
     *
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'type_id');
    }

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
     * Set name attribute and auto-generate slug
     *
     * @param $value
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;

        // Auto-generate slug if not set
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * get title
     */
    public function getTitleAttribute()
    {
        return $this->name;
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
     * Relation to post type
     * @return HasOne
     */
    public function postType(): HasOne
    {
        return $this->hasOne(PostType::class);
    }
}
