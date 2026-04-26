<?php

namespace Javaabu\Cms\Media;

use Javaabu\Cms\Enums\JsonTranslatable\IsJsonTranslatable;
use Javaabu\Cms\Enums\JsonTranslatable\JsonTranslatable;
use Javaabu\Cms\Enums\Languages;
use Spatie\Image\Image;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements
    AdminModel,
    JsonTranslatable
{

    use LogsActivity;
    use IsAdminModel;
    use IsJsonTranslatable;

    /**
     * The attributes that would be logged
     *
     * @var array
     */
    protected static $logAttributes = ['*'];
    /**
     * Log only changed attributes
     *
     * @var boolean
     */
    protected static $logOnlyDirty = true;
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
        $query->translationsSearch('description', $search, $locale)
              ->orWhere('name', 'like', '%' . $search . '%')
              ->orWhereHas('tagWords', function ($tags) use ($search, $locale) {
                  $tags->search($search, $locale);
              });

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
    public function scopeUserVisible($query)
    {
        $userModel = config('auth.providers.users.model') ?: 'App\Models\User';

        $admin = auth()->user() instanceof $userModel
            ? auth()->user()
            : auth()->guard('web_admin')->user();

        if ($admin) {
            if ($admin->can('create', static::class)) {
                if ($admin->can('edit_other_users_media')) {
                    return $query;
                } else {
                    return $query->whereModelType($admin->getMorphClass())
                        ->whereModelId($admin->id);
                }
            }
        }

        return $query->whereId(-1);
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
        return $this->url($action, $locale, 'Member\\');
    }

    /**
     * Get the member localized url
     *
     * @param null $locale
     * @return string
     */
    public function getMemberLocalizedUrl($locale = null)
    {
        return $this->url('show', $locale, 'Member\\');
    }

    /**
     * Get the width attribute
     *
     * @return int
     */
    public function getWidthAttribute()
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
    public function getHeightAttribute()
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

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute():string
    {
        return translate_route('admin.media.edit', $this);
    }
}





