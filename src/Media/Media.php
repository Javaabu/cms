<?php

namespace Javaabu\Cms\Media;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Cms\Enums\Languages;
use Spatie\Image\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Javaabu\Cms\Translatable\IsJsonTranslatable;
use Javaabu\Mediapicker\Models\Media as BaseMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends BaseMedia
{
    use IsJsonTranslatable {
        getAdminLocalizedUrl as protected getTranslatableAdminLocalizedUrl;
        getAdminUrlAttribute as protected getTranslatableAdminUrlAttribute;
        getLocalizedUrl as protected getTranslatableLocalizedUrl;
        getAdminLocalizedEditUrl as protected getTranslatableAdminLocalizedEditUrl;
        url as protected translatableUrl;
    }

    /**
     * The attributes that would be logged
     *
     * @var array
     */
    protected static array $logAttributes = ['*'];
    /**
     * Log only changed attributes
     *
     * @var boolean
     */
    protected static bool $logOnlyDirty = true;
    protected $morphClass = 'media';
    protected $attributes = [
        'lang' => Languages::DV,
    ];

    /**
     * The attributes that are cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'translations'          => 'array',
        'manipulations'         => 'array',
        'custom_properties'     => 'array',
        'generated_conversions' => 'array',
        'responsive_images'     => 'array',
        'hide_translation'      => 'boolean',
        'lang'                  => Languages::class
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = [
        'description',
        'name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'name',
    ];

    /**
     * A search scope
     *
     * @param        $query
     * @param        $search
     * @param null $locale
     * @return
     */
    public function scopeSearch($query, $search, $locale = null):mixed
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->translationsSearch('description', $search, $locale)
                ->orWhere('name', 'like', '%' . $search . '%');
        } else {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (method_exists($this, 'tagWords')) {
            $query->orWhereHas('tagWords', function ($tags) use ($search, $locale) {
                $tags->search($search, $locale);
            });
        }

        return $query;
    }

    /**
     * With relations scope
     *
     * @param $query
     * @return
     */
    public function scopeWithRelations($query)
    {
        return $query->with('model');
    }

    /**
     * User visible
     *
     * @param $query
     * @return mixed
     */
    public function scopeUserVisible(Builder $query): void
    {
        $userModel = config('auth.providers.users.model') ?: 'App\Models\User';

        $admin = auth()->user() instanceof $userModel
            ? auth()->user()
            : auth()->guard('web_admin')->user();

        if ($admin) {
            if ($admin->can('create', static::class)) {
                if ($admin->can('edit_other_users_media')) {
                    return;
                } else {
                    $query->whereModelType($admin->getMorphClass())
                        ->whereModelId($admin->id);

                    return;
                }
            }
        }

        $query->whereId(-1);
    }


    /**
     * Get url attribute
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return $this->getUrl();
    }

    /**
     * Get type attribute
     *
     * @return string
     */
    public function getTypeSlugAttribute()
    {
        return AllowedMimeTypes::getType($this->mime_type);
    }

    /**
     * Get icon attribute
     *
     * @return string
     */
    public function getIconAttribute()
    {
        $icon = AllowedMimeTypes::getIcon($this->mime_type);

        return 'zmdi zmdi-' . ($icon ?: 'file');
    }

    /**
     * Get web icon attribute
     *
     * @return string
     */
    public function getWebIconAttribute()
    {
        $icon = AllowedMimeTypes::getWebIcon($this->mime_type);

        return 'fa fa-' . ($icon ?: 'file');
    }

    /**
     * Get web icon attribute
     *
     * @return string
     */
    public function getWebIconLightAttribute()
    {
        $icon = AllowedMimeTypes::getWebIcon($this->mime_type);

        return 'fal fa-' . ($icon ?: 'file');
    }

    /**
     * Type scope
     *
     * @param          $query
     * @param string $type
     * @return
     */
    public function scopeHasType($query, $type)
    {
        return $query->whereIn('mime_type', AllowedMimeTypes::getAllowedMimeTypes($type));
    }

    /**
     * Get the member url attribute
     *
     * @return string
     */
    public function getMemberUrlAttribute()
    {
        return $this->memberUrl();
    }

    /**
     * Returns the member url
     *
     * @param string $action
     * @param null $locale
     * @return string
     */
    public function memberUrl($action = 'show', $locale = null)
    {
        return $this->mediaRouteUrl($action, namespace: 'Member\\');
    }

    /**
     * Get the member localized url
     *
     * @param null $locale
     * @return string
     */
    public function getMemberLocalizedUrl($locale = null)
    {
        return $this->mediaRouteUrl('show', namespace: 'Member\\');
    }

    public function getAdminUrlAttribute(): string
    {
        return $this->mediaRouteUrl('edit');
    }

    public function getAdminLocalizedUrl($locale = null): string
    {
        return $this->mediaRouteUrl('show');
    }

    public function getAdminLocalizedEditUrl($locale = null): string
    {
        return $this->mediaRouteUrl('edit');
    }

    public function getLocalizedUrl($locale = null): string
    {
        return $this->getUrl();
    }

    public function url(string $action, ?SpatieMedia $media = null): string
    {
        return $this->mediaRouteUrl($action, $media);
    }

    protected function mediaRouteUrl(string $action = 'show', ?SpatieMedia $media = null, string $namespace = 'admin'): string
    {
        $route = $namespace . '.media.' . $action;
        $params = [];

        if (! in_array($action, ['index', 'store', 'create', 'bulk', 'picker'])) {
            $params[] = $media ?: $this;
        }

        return URL::route($route, $params);
    }

    /**
     * Get the width attribute
     *
     * @return int
     */
    public function getWidthAttribute(): ?int
    {
        if (! $this->hasCustomProperty('width')) {
            $this->saveDimensions();
        }

        return $this->getCustomProperty('width');
    }

    /**
     * Save the image dimensions
     */
    public function saveDimensions()
    {
        $image = Image::load($this->getUrl());

        $this->setCustomProperty('width', $image->getWidth());
        $this->setCustomProperty('height', $image->getHeight());
        $this->save();
    }

    /**
     * Get the height attribute
     *
     * @return int
     */
    public function getHeightAttribute(): ?int
    {
        if (! $this->hasCustomProperty('height')) {
            $this->saveDimensions();
        }

        return $this->getCustomProperty('height');
    }

    public function getShortNameAttribute()
    {
        return Str::limit($this->name, 15);
    }

}
