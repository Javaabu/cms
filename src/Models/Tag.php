<?php

namespace Javaabu\Cms\Models;

use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Helpers\Traits\HasSlug;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

class Tag extends Model implements AdminModel
//    , Translatable
{
    use IsAdminModel;
    use LogsActivity;
//    use HasSlugForTranslatables;
//    use IsJsonTranslatable;

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

    /**
     * The attributes that are cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
    ];

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchable = ['name',];

    public function getTranslatables(): array
    {
        return [
            'name',
        ];
    }

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return route('admin.tags.show', $this);
    }

    /**
     * Sanitize the name
     *
     * @param string $text
     * @param string $special_char
     * @return string
     */
    public static function sanitizeName(string $text, string $special_char = ' '): string
    {
        return str($text)->slug($special_char);
    }

    /**
     * Get the slug attribute
     *
     * @return string
     */
    public function getSlugAttribute(): string
    {
        return str($this->translate('name', 'en'))->slug();
    }

    /**
     * Has slug scope
     *
     * @param $query
     * @param $slug
     * @return mixed
     */
    public function scopeHasSlug($query, $slug)
    {
        // replace - with spaces
        $actual_slug = str($slug)->slug(' ');

        return $this->whereName($actual_slug);
    }

    /**
     * Get localized admin URL
     *
     * @return mixed|string
     */
    public function getAdminLocalizedUrl()
    {
        return $this->admin_url;
    }
}
