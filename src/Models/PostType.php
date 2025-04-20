<?php

namespace Javaabu\Cms\Models;

use Illuminate\Support\Str;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;
use Javaabu\Translatable\Languages;

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
        'translations' => 'array',
        'lang' => Languages::class,
        'hide_translation' => 'boolean',
    ];

    public function getAdminUrlAttribute(): string
    {
        // TODO: Implement getAdminUrlAttribute() method.
        return 'post_type';
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
}

