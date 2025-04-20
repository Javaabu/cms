<?php

namespace Javaabu\Cms\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Javaabu\Cms\Enums\GalleryTypes;
use Javaabu\Cms\Enums\PageStyles;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Helpers\Enums\PublishStatuses;
use Javaabu\Helpers\Traits\HasSlug;
use Javaabu\Helpers\Traits\Publishable;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;
use Javaabu\Translatable\Models\Language;

class Post extends Model implements
    AdminModel,
    Translatable
{
    use IsJsonTranslatable;
    use IsAdminModel;
    use SoftDeletes;
    use Publishable;
    use HasSlug;
    use IsJsonTranslatable;

    protected static $status_class = PublishStatuses::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'published_at',
        'document_no',
        'expire_at',
        'format',
        'video_url',
        'page_style',
        'ref_no',
    ];

    /**
     * The attributes that are cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'title'            => 'string',
        'slug'             => 'string',
        'content'          => 'string',
        'excerpt'          => 'string',
        'menu_order'       => 'integer',
        'status'           => PublishStatuses::class,
        'published_at'     => 'datetime',
        'document_no'      => 'string',
        'expire_at'        => 'datetime',
        'format'           => GalleryTypes::class,
        'video_url'        => 'string',
        'page_style'       => PageStyles::class,
        'ref_no'           => 'string',
        'recently_updated' => 'boolean',
        'translations'     => 'array',
        'hide_translation' => 'boolean',
    ];

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchable = [
        'title',
        'content',
        'document_no',
    ];

    protected $with = ['postType'];

    public function getTranslatables(): array
    {
        if (! config('cms.should_translate')) {
            return [];
        }
        return [
            'title',
            'content',
            'excerpt',
        ];
    }
    public function getNonTranslatablePivots(): array
    {
        if (! config('cms.should_translate')) {
            return [];
        }
        return [
            'categories',
            'tagWords',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (empty($model->published_at)) {
                $model->published_at = null;
            }
        });
    }

    /**
     * Convert dates to Carbon
     */
    public function setPublishedAtAttribute($value)
    {
        return $this->attributes['published_at'] = $value ? Carbon::parse($value) : now();
    }

    /**
     * Convert dates to Carbon
     */
    public function setExpireAtAttribute($value)
    {
        return $this->attributes['expire_at'] = $value ? Carbon::parse($value) : null;
    }


    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return route('admin.posts.show', [$this->type, $this]);
    }

    /**
     * Get the admin localized url
     *
     * @param null $locale
     * @return string
     */
    public function getAdminLocalizedUrl($locale = null)
    {
        return $this->url('show', $locale);
    }

    /**
     * Get the name for the admin link
     *
     * @return string
     */
    public function getAdminLinkNameAttribute(): string
    {
        return $this->title;
    }

    /**
     * Returns the url
     *
     * @param string $action
     * @param string|null $locale
     * @param string $namespace
     * @return string
     */
    public function url(string $action = 'show', string $locale = null, string $namespace = 'cms::'): string
    {
        $controller = Str::lower(Str::plural(Str::kebab(class_basename(get_class($this)))));
        $controller_action = $namespace . '.' . $controller . '.' . $action;

        $params = [
            'post_type' => $this->postType->slug,
        ];

        if (config('cms.should_translate')) {
            if (! $locale) {
                $locale = app()->getLocale();
            }

            if ($locale instanceof Language) {
                $locale = $locale->value;
            }

            $params[] = $locale ?: app()->getLocale();
        }

        if (! in_array($action, ['index', 'store', 'create', 'trash'])) {
            $params[] = $this->id;
        }

        $url = URL::route($controller_action, $params);

        return $url;
    }

    public function postType(): BelongsTo
    {
        return $this->belongsTo(PostType::class, 'type', 'slug');
    }


    /**
     * A search scope
     *
     * @param        $query
     * @param        $search
     * @param null $locale
     * @return mixed
     */
    public function scopeSearch($query, $search, $locale = null): mixed
    {
        // fulltext search on db values or
        return $query->translationsSearch('title', $search, $locale);
    }

    /**
     * A scheduled scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeScheduled($query)
    {
        return $query->where($this->getTable() . '.published_at', '>', Carbon::now())
            ->where($this->getTable() . '.status', $this->getPublishedKey());
    }

    /**
     * A published scope
     *
     * @param $query
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where($this->getTable() . '.published_at', '<=', Carbon::now())
            ->where($this->getTable() . '.status', $this->getPublishedKey());
    }

    /**
     * Scope that provides ability to view post previews where has valid signature.
     *
     * @param $query
     * @return mixed
     */
    public function scopePublishedOrPreview($query)
    {
        if (request()->hasValidSignature()) {
            return $query;
        }

        return $query->published();
    }

    /**
     * A year scope
     *
     * @param $query
     * @return mixed
     */
    public function scopePublishedByYear($query, $year)
    {
        return $query->whereYear('published_at', $year);
    }

    /**
     * Scope to get a particular type
     *
     * @param Builder $query
     * @param           $type
     * @return Builder
     */
    public function scopePostType(Builder $query, $type): Builder
    {
        return $query->whereHas('postType', function ($query) use ($type) {
            return $query->whereSlug($type);
        });
    }

    /**
     * Scope to get a particular type
     *
     * @param Builder $query
     * @param           $type
     * @return Builder
     */
    public function scopeQueryPostType(Builder $query, $type): Builder
    {
        return $query->whereHas('postType', function ($query) use ($type) {
            return $query->whereSlug($type);
        });
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
        if (! $locale) {
            $locale = app()->getLocale();
        }

        if ($this->lang->value != $locale && (is_null($this->translations) || $this->hide_translation)) {
            return null;
        }

        $post_type_slug = $this->postType->slug;
        $controller = Str::lower(Str::plural(Str::kebab(class_basename(get_class($this)))));

        if ($post_type_slug != 'pages') {
            $controller_action = 'cms::' . $controller . '.' . $action . '.' . $post_type_slug;
        } else {
            $controller_action = 'cms::pages.' . $action;
        }

        $params = [$locale, $this->slug];

        return URL::route($controller_action, $params);
    }

    /**
     * Update the status
     *
     * Modified to send $this as an argument to the `can` method
     *
     * @param        $status | desired status
     * @param bool $publish | send for approving
     * @return void
     */
    public function updateStatus($status, bool $publish = false): void
    {
        //first check if requesting for publishing
        if ($publish || $status == $this->getPublishedKey()) {
            $this->publish();
        } elseif ($status == $this->getRejectedKey()) {
            $this->reject();
        } elseif ($status && auth()->check() && auth()->user()->can('publish', [static::class, $this])) {
            $this->status = $status;
        } else {
            $this->draft();
        }
    }

    /**
     * Publish the post
     *
     * Modified to send the postType instead of static::class
     *
     * @return void
     */
    public function publish(): void
    {
        if ($user = auth()->user()) {
            $this->status = $user->can('publish', $this->postType) ? $this->getPublishedKey()
                : $this->getPendingKey();
        }
    }

    /**
     * Reject the post
     *
     * Modified to send the postType instead of static::class
     *
     * @return void
     */
    public function reject(): void
    {
        if ($user = auth()->user()) {
            $this->status = $user->can('publish', $this->postType) ? $this->getRejectedKey()
                : $this->getDraftKey();
        }
    }

    /**
     * Determine posts that a user can see
     *
     * @param            $query
     * @param PostType $type
     * @return mixed
     */
    public function scopeUserVisibleForPostType($query, PostType $type)
    {
        $admin = auth()->user();

        if ($admin) {
            if ($admin->can('create', $type) && $admin->can('editOthers', $type)) {
                // Admin can edit all posts
                return $query;
            }
        }

        // everyone can view published
        return $query->published();
    }

    /**
     * Get the content blocks
     * Default to one single raw block
     *
     * @return array
     */
    public function getContentBlocksAttribute(): array
    {
        $blocks = json_decode($this->content, true);

        if ($blocks) {
            return $blocks['blocks'];
        } else {
            return [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $this->content,
                    ],
                ],
            ];
        }
    }
}
