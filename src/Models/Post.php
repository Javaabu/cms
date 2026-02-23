<?php

namespace Javaabu\Cms\Models;

use App\Helpers\Translation\JsonTranslatable\JsonTranslatable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Javaabu\Activitylog\Traits\LogsActivity;
use Javaabu\Cms\Enums\HaveCategories;
use Javaabu\Cms\Enums\IsExpirable;
use Javaabu\Cms\Enums\JsonTranslatable\IsJsonTranslatable;
use Javaabu\Cms\Enums\Languages;
use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Helpers\Enums\PublishStatuses;
use Javaabu\Helpers\Media\AllowedMimeTypes;
use Javaabu\Helpers\Traits\HasSlug;
use Javaabu\Helpers\Traits\Publishable;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements
    AdminModel,
    HasAttachments,
    JsonTranslatable
{
    use SoftDeletes;
    use IsAdminModel;
    use LogsActivity;
    use SoftDeletes;
    use Publishable;
    use HasSlug;
    use HaveCategories;
   // use IsTaggable;
    use IsJsonTranslatable;
    //use WithDepartmentPermissions;
    use IsExpirable;
    use InteractsWithAttachments;


    protected static $status_class = PublishStatuses::class;

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
    protected $fillable = [
        'type',
        'user_id',
        'department_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'menu_order',
        'status',
        'published_at',
        'document_no',
        'expire_at',
        'format',
        'video_url',
        'page_style',
        'ref_no',
        'gazette_link',
        'sidebar_menu_id',
        'recently_updated',
        'last_updated_at',
        'meta_title',
        'meta_description',
        'og_image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'expire_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'recently_updated' => 'boolean',
        'menu_order' => 'integer',
        'lang' => Languages::class,
    ];

    protected $with = ['postType', 'categories'];

    /**
     * Get the route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Post type relationship
     *
     * @return BelongsTo
     */
    public function postType(): BelongsTo
    {
        return $this->belongsTo(PostType::class, 'type', 'slug');
    }

    /**
     * User relationship (author)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Department relationship
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo('App\Models\Department', 'department_id');
    }

    /**
     * Categories relationship
     *
     * @return MorphToMany
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'model', 'category_model');
    }

    /**
     * Scope to filter by post type
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopePostType(Builder $query, $type): Builder
    {
        if ($type instanceof PostType) {
            $type = $type->slug;
        }

        return $query->where('type', $type);
    }

    /**
     * Scope to search posts
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch($query, $search): Builder
    {
        return $query->where('title', 'like', "%{$search}%")
            ->orWhere('content', 'like', "%{$search}%");
    }

    /**
     * Scope to filter published posts
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::PUBLISHED->value)
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to filter scheduled posts
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::SCHEDULED->value)
            ->where('published_at', '>', now());
    }

    /**
     * Scope to filter by published year
     *
     * @param Builder $query
     * @param int $year
     * @return Builder
     */
    public function scopePublishedByYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('published_at', $year);
    }

    /**
     * Scope to order by menu order
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('menu_order')->orderBy('published_at', 'desc');
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
     * Set published at attribute
     *
     * @param $value
     */
    public function setPublishedAtAttribute($value): void
    {
        $this->attributes['published_at'] = $value ? Carbon::parse($value) : now();
    }

    /**
     * Set expire at attribute
     *
     * @param $value
     */
    public function setExpireAtAttribute($value): void
    {
        $this->attributes['expire_at'] = $value ? Carbon::parse($value) : null;
    }

    /**
     * Get title attribute
     */
    public function getNameAttribute(): string
    {
        return $this->title;
    }

    /**
     * Get title attribute for display
     */
    public function getTitleAttrAttribute(): string
    {
        return Str::limit($this->title, 50);
    }

    /**
     * Check if post is published
     */
    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED->value
            && $this->published_at <= now();
    }

    /**
     * Check if post is expired
     */
    public function isExpired(): bool
    {
        return $this->expire_at && $this->expire_at < now();
    }

    /**
     * Publish the post
     */
    public function publish(): void
    {
        $this->update([
            'status' => PostStatus::PUBLISHED->value,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Reject the post
     */
    public function reject(): void
    {
        $this->update([
            'status' => PostStatus::REJECTED->value,
        ]);
    }

    /**
     * Update post status
     *
     * @param string $status
     * @param bool $publish
     */
    public function updateStatus(string $status, bool $publish = false): void
    {
        $data = ['status' => $status];

        if ($publish && !$this->published_at) {
            $data['published_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Check if slug is unique within post type
     *
     * @param string $value
     * @return bool
     */
    public function isUniqueSlug(string $value): bool
    {
        $query = static::withTrashed()
            ->where('slug', $value)
            ->where('type', $this->type);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->count() === 0;
    }

    /**
     * Get similar posts by category
     *
     * @param string $relation
     * @return Builder
     */
    public function similarByCategory(string $relation = 'categories'): Builder
    {
        $categoryIds = $this->{$relation}->pluck('id');

        return static::query()
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->published()
            ->whereHas($relation, function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->orderBy('published_at', 'desc');
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
        $admin = auth()->user() instanceof User ?
            auth()->user() :
            auth()->guard('web_admin')->user();

        if ($admin) {
            if ($admin->can('create', $type)) {
                if ($admin->can('editOthers', $type)) {
                    // Admin can edit all posts
                    return $query;
                } elseif (Schema::hasTable('departments')) {
                    return $query->whereIn('department_id', $admin->departments->pluck('id')->all());
                }
            }
        }

        // everyone can view published
        return $query->published();
    }

    public function registerAttachmentCollections()
    {
        $this->addAttachmentCollection('featured_image')
            ->singleFile()
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType, 'image');
            })
            ->withResponsiveImages();

        // Register attachment collections regardless of available features
        $this->addAttachmentCollection('documents')
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType, 'document');
            });

        $this->addAttachmentCollection('documents_translated')
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType, 'document');
            });

        $this->addAttachmentCollection('image_gallery')
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType, 'image');
            });
    }

    public function ogImageCollection()
    {
        return 'featured_image';
    }

    public function registerAttachmentConversions(Media $media = null)
    {
        $this->addAttachmentConversion('og_image')
            ->width(1200)
            ->height(630)
            ->fit(Fit::Crop, 1200, 630)
            ->performOnCollections('featured_image');

        $this->addAttachmentConversion('shareable_image')
            ->width(1200)
            ->height(630)
            ->fit(Fit::Crop, 1200, 630)
            ->performOnCollections('featured_image')
            ->nonQueued();

        $this->addAttachmentConversion('publication_thumb')
            ->width(76)
            ->height(100)
            ->fit(Fit::Crop, 76, 100)
            ->performOnCollections('featured_image');

        $this->addAttachmentConversion('news_thumb')
            ->width(390)
            ->height(384)
            ->fit(Fit::Crop, 390, 384)
            ->performOnCollections('featured_image');

        $this->addAttachmentConversion('thumb_sm')
            ->width(200)
            ->height(150)
            ->fit(Fit::Crop, 200, 150)
            ->performOnCollections('featured_image')
            ->nonQueued();

        $this->addAttachmentConversion('square_thumb')
            ->width(200)
            ->height(200)
            ->fit(Fit::Crop, 200, 200)
            ->performOnCollections('featured_image')
            ->nonQueued();

        $this->addAttachmentConversion('thumb_md')
            ->width(600)
            ->height(400)
            ->fit(Fit::Crop, 600, 400)
            ->performOnCollections('featured_image')
            ->nonQueued();

        $this->addAttachmentConversion('thumb_lg')
            ->width(1000)
            ->height(1000)
            ->fit(Fit::Crop, 1000, 1000)
            ->performOnCollections('featured_image')
            ->nonQueued();

        $this->addAttachmentConversion('large')
            ->width(1200)
            ->height(800)
            ->fit(Fit::Max, 1200, 800)
            ->performOnCollections('featured_image')
            ->nonQueued();
    }
}
