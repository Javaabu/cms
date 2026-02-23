<?php

namespace Javaabu\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;

class PostType extends Model
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
        'icon',
        'category_type_id',
        'features',
        'description',
        'og_description',
        'order_column',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'features' => 'array',
        'order_column' => 'integer',
    ];

    /**
     * Get the route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the permission slug
     *
     * @return string
     */
    public function getPermissionSlugAttribute(): string
    {
        return Str::slug($this->slug, '_');
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
     * Relationship to category type
     *
     * @return BelongsTo
     */
    public function categoryType(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class);
    }

    /**
     * Get categories for this post type
     */
    public function categoriesFor()
    {
        if (!$this->category_type_id) {
            return collect([]);
        }

        return Category::where('type_id', $this->category_type_id)
            ->defaultOrder()
            ->get();
    }

    /**
     * Set slug attribute
     *
     * @param $value
     */
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Get title attribute
     */
    public function getTitleAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get lowercase name
     */
    public function getLowerNameAttribute(): string
    {
        return Str::lower($this->name);
    }

    /**
     * Get lowercase singular name
     */
    public function getLowerSingularNameAttribute(): string
    {
        return Str::lower($this->singular_name);
    }

    /**
     * Check if post type has a feature
     *
     * @param string|PostTypeFeatures $feature
     * @return bool
     */
    public function hasFeature(string|PostTypeFeatures $feature): bool
    {
        if ($feature instanceof PostTypeFeatures) {
            $feature = $feature->value;
        }

        return is_array($this->features) && array_key_exists($feature, $this->features);
    }

    /**
     * Get feature name/label
     *
     * @param string|PostTypeFeatures $feature
     * @return string|null
     */
    public function getFeatureName(string|PostTypeFeatures $feature): ?string
    {
        if ($feature instanceof PostTypeFeatures) {
            $feature = $feature->value;
        }

        if (!$this->hasFeature($feature)) {
            return null;
        }


        $feature_title = $this->features[$feature];

        // If it's a boolean, use the enum label
        if (is_bool($feature_title)) {
            $enum = PostTypeFeatures::from($feature);
            return $enum->label();
        }

        // If it's a string, return it as title case
        if (is_string($feature_title)) {
            return Str::title($feature_title);
        }

        return null;
    }

    /**
     * Get feature collection name
     *
     * @param string|PostTypeFeatures $feature
     * @return string|null
     */
    public function getFeatureCollectionName(string|PostTypeFeatures $feature): ?string
    {
        if ($feature instanceof PostTypeFeatures) {
            $feature = $feature->value;
        }

        if (!$this->hasFeature($feature)) {
            return null;
        }

        $enum = PostTypeFeatures::from($feature);
        return $enum?->getCollectionName();
    }

    /**
     * Get the view to use for the post type
     *
     * @param string $action
     * @return string
     */
    public function getWebView(string $action = 'index'): string
    {
        return 'cms::web.post-type.' . $this->slug . '.' . $action;
    }

    /**
     * Get paginator count for the post type
     *
     * @return int
     */
    public function getPaginatorCount(): int
    {
        return config('cms.pagination.per_page', 15);
    }
}
