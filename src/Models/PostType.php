<?php

namespace Javaabu\Cms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

class PostType extends Model implements AdminModel, Translatable
{
//    use HasFactory;
    use IsAdminModel;
    use IsJsonTranslatable;
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
        'singular_name' => 'string',
        'slug' => 'string',
        'icon' => 'string',
        'features' => 'array',
        'og_description' => 'string',
        'order_column' => 'integer',
        'hide_translation' => 'boolean',
    ];

    public function getAdminUrlAttribute(): string
    {
        return 'post_type';
    }

    /**
     * Get the permission slug
     *
     * @return string
     */
    public function getPermissionSlugAttribute()
    {
        return Str::slug($this->slug, '_');
    }

    /**
     * Get the route key name
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getTranslatables(): array
    {
        if (! config('cms.should_translate')) {
            return [];
        }

        return [
            'name',
            'singular_name',
            'og_description',
        ];
    }

    public function categoryType(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class);
    }

    public function getFeatureName($feature): ?string
    {
        if (! $this->hasFeature($feature)) {
            return null;
        }

        $feature_title = $this->features[$feature];

        if (gettype($feature_title) == 'boolean') {
            return PostTypeFeatures::getLabel($feature);
        }

        if (gettype($feature_title) == 'string') {
            return Str::title($feature_title);
        }

        return null;
    }

    public function hasFeature($feature): bool
    {
        return array_key_exists($feature, $this->features);
    }

    public function getFeatureCollectionName($feature): ?string
    {
        if (! $this->hasFeature($feature)) {
            return null;
        }

        return PostTypeFeatures::getCollectionName($feature);
    }

    /**
     * A post type has many posts
     */
    public function userVisiblePosts()
    {
        return $this->posts()->userVisibleForPostType($this);
    }

    /**
     * A post type has many posts
     *
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'type', 'slug');
    }

    /**
     * Slugify the value
     *
     * @param $value
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }
}

