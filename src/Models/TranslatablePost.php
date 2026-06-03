<?php

namespace Javaabu\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatable;
use Javaabu\Cms\Enums\JsonTranslatable\IsJsonTranslatable;

class TranslatablePost extends Post implements JsonTranslatable
{
    use IsJsonTranslatable;

    protected $table = 'posts';

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(['lang']);
        $this->mergeCasts([
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
        'title',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
    ];

    public function getMorphClass()
    {
        return 'post';
    }

    /**
     * A search scope that searches translatable fields
     */
    public function scopeSearch($query, $search, $locale = null): Builder
    {
        return $query->translationsSearch('title', $search, $locale)
            ->orWhere(fn ($q) => $q->translationsSearch('content', $search, $locale))
            ->orWhere(fn ($q) => $q->translationsSearch('excerpt', $search, $locale));
    }

    /**
     * Get the permalink
     *
     * @return string|null
     */
    public function getPermalinkAttribute(): ?string
    {
        $locale = $this->lang?->value ?? app()->getLocale();
        $postTypeSlug = $this->postType->slug;

        // Check if translations exist for the current locale
        if (!$this->hasTranslations($locale)) {
            $locale = \Javaabu\Cms\Enums\Languages::getOppositeLocale($locale);
        }

        try {
            // For custom post types registered via Routes::customPostType
            // Route name format: web.post-types.{postTypeSlug}.show (e.g., web.post-types.news.show)
            return route("web.post-types.{$postTypeSlug}.show", [$locale, $this->slug]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::debug('Permalink generation failed for post ' . $this->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the permalink
     *
     * @return string
     */
    public function getPreviewLinkAttribute(): string
    {
        $postTypeSlug = $this->postType->slug;
        $locale = app()->getLocale();

        // Check if translations exist for the current locale
        if (!$this->hasTranslations($locale)) {
            $locale = \Javaabu\Cms\Enums\Languages::getOppositeLocale($locale);
        }

        try {
            // For custom post types registered via Routes::customPostType
            // Route name format: web.post-types.{postTypeSlug}.show
            return \Illuminate\Support\Facades\URL::temporarySignedRoute(
                "web.post-types.{$postTypeSlug}.show",
                now()->addDay(),
                [$locale, $this->slug]
            );
        } catch (\Exception $e) {
            return \Illuminate\Support\Facades\URL::to('/');
        }
    }

    /**
     * Returns the url
     *
     * @param string $action
     * @param string|null $locale
     * @return string|null
     */
    public function translatedPermalink(string $action = 'show', string $locale = null): ?string
    {
        if (!$locale) {
            $locale = app()->getLocale();
        }

        if ($this->lang?->value != $locale && (is_null($this->translations) || $this->hide_translation)) {
            return null;
        }

        $postTypeSlug = $this->postType->slug;

        try {
            // For custom post types registered via Routes::customPostType
            // Route name format: web.post-types.{postTypeSlug}.{action}
            return \Illuminate\Support\Facades\URL::route("web.post-types.{$postTypeSlug}.{$action}", [$locale, $this->slug]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns the url
     *
     * Override translatable trait behavior for posts so admin routes include
     * required post type parameter: {language}/{post_type}/{post}.
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

        $preferredRoute = $namespace . '.posts.' . $action;
        $fallbackRoute = 'posts.' . $action;
        $postType = $this->postType ?? $this->type;
        $params = [$locale, $postType];

        if (! in_array($action, ['index', 'store', 'create', 'trash'])) {
            $params[] = $this->id;
        }

        try {
            return URL::route($preferredRoute, $params);
        } catch (\Throwable $e) {
            return URL::route($fallbackRoute, $params);
        }
    }

    /**
     * Translatable posts should point admin_url to the edit page.
     */
    public function getAdminUrlAttribute(): string
    {
        return $this->getAdminLocalizedEditUrl();
    }

    /**
     * Keep getAdminLocalizedUrl consistent with post admin UX (edit page).
     *
     * @param string|null $locale
     * @return string
     */
    public function getAdminLocalizedUrl($locale = null)
    {
        return $this->url('edit', $locale);
    }
}
