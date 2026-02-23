<?php

namespace Javaabu\Cms\Media\Attachment\HasAttachments;

use DateTimeInterface;
use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\Conversions\Conversion;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Javaabu\Cms\Media\Attachment\AttachmentRepository;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Javaabu\Cms\Media\Attachment\MediaAdder\MediaAdder;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Javaabu\Cms\Media\Attachment\MediaAdder\MediaAdderFactory;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeDeleted;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Cms\Media\Attachment\Events\AttachmentCollectionHasBeenCleared;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded\MediaMimeTypeNotAllowed;

trait HasAttachmentsTrait
{
    /** @var array */
    public $attachmentConversions = [];

    /** @var array */
    public $attachmentCollections = [];

    /** @var array */
    protected $unAttachedAttachmentItems = [];

    public static function bootHasAttachmentsTrait()
    {
        static::deleting(function (HasAttachments $entity) {
            if (in_array(SoftDeletes::class, class_uses_recursive($entity))) {
                if (! $entity->forceDeleting) {
                    return;
                }
            }

            $entity->attachments()->get()->each->delete();
        });
    }

    /**
     * Set the polymorphic relation.
     *
     * @return MorphMany
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'model');
    }

    /**
     * Add a media from a request.
     *
     * @param string $key
     *
     * @return MediaAdder
     */
    public function addAttachmentFromRequest(string $key)
    {
        return app(MediaAdderFactory::class)->createFromRequest($this, $key);
    }

    /**
     * Add multiple medias from a request by keys.
     *
     * @param string[] $keys
     *
     * @return MediaAdder[]
     */
    public function addMultipleAttachmentsFromRequest(array $keys)
    {
        return app(MediaAdderFactory::class)->createMultipleFromRequest($this, $keys);
    }

    public function hasAttachments(string $collectionName = 'default'): bool
    {
        return count($this->getAttachments($collectionName)) ? true : false;
    }

    /*
     * Determine if there is an attachment in the given collection.
     */

    /**
     * Get attachment collection by its collectionName.
     *
     * @param string $collectionName
     * @param array|callable $filters
     *
     * @return Collection
     */
    public function getAttachments(string $collectionName = 'default', $filters = []): Collection
    {
        return app(AttachmentRepository::class)->getCollection($this, $collectionName, $filters);
    }

    /**
     * Get the attachment media
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Collection
     */
    public function getAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Collection
    {
        $attachments = $this->getAttachments($collectionName, $filters);

        return $attachments->pluck('media');
    }

    public function getFirstAttachmentUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getUrl($conversionName);
    }

    /**
     * Get the first attachment media
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Attachment
     */
    public function getFirstAttachment(string $collectionName = 'default', array $filters = []): ?Attachment
    {
        $attachments = $this->getAttachments($collectionName, $filters);

        return $attachments->first();
    }

    public function getFirstAttachmentTemporaryUrl(DateTimeInterface $expiration, string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getTemporaryUrl($expiration, $conversionName);
    }

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */

    public function getFirstAttachmentPath(string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getPath($conversionName);
    }

    /*
     * Get the url of the image for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */

    /**
     * Update an attachment collection by deleting and inserting again with new values.
     *
     * @param array $newAttachmentsArray
     * @param string $collectionName
     *
     * @return Collection
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachments(array $newAttachmentsArray, string $collectionName = 'default'): Collection
    {
        $this->removeAttachmentItemsNotPresentInArray($newAttachmentsArray, $collectionName);

        return collect($newAttachmentsArray)
            ->map(function (array $newAttachmentItem) use ($collectionName) {
                static $orderColumn = 1;

                $attachmentClass = Attachment::class;
                $currentAttachment = $attachmentClass::findOrFail($newAttachmentItem['id']);

                if ($currentAttachment->collection_name !== $collectionName) {
                    throw AttachmentCannotBeUpdated::doesNotBelongToCollection($collectionName, $currentAttachment);
                }

                $currentAttachment->order_column = $orderColumn++;

                $currentAttachment->save();

                return $currentAttachment;
            });
    }

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */

    /**
     * Remove attachments not in the array
     *
     * @param array $newAttachmentsArray
     * @param string $collectionName
     */
    protected function removeAttachmentItemsNotPresentInArray(array $newAttachmentsArray, string $collectionName = 'default')
    {
        $this->getAttachments($collectionName)
             ->reject(function (Attachment $currentAttachmentItem) use ($newAttachmentsArray) {
                 return in_array($currentAttachmentItem->id, array_column($newAttachmentsArray, 'id'));
             })
            ->each->delete();
    }

    /**
     * Update an attachment collection by deleting and inserting media again with new values.
     *
     * @param array $newMediaArray
     * @param string $collectionName
     *
     * @return Collection
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachmentMedia(array $newMediaArray, string $collectionName = 'default'): Collection
    {
        $this->removeAttachmentMediaNotPresentInArray($newMediaArray, $collectionName);

        // filter out already attached media
        $existing_media = $this->getAttachments($collectionName)
                               ->pluck('media_id')
                               ->all();

        $newMediaArray = array_diff($newMediaArray, $existing_media);

        // only include distince values
        $newMediaArray = array_unique($newMediaArray);

        return collect($newMediaArray)
            ->map(function ($newMediaId) use ($collectionName) {
                static $orderColumn = 1;

                $currentAttachment = $this->getFirstAttachment($collectionName, ['media_id' => $newMediaId]);

                if (! $currentAttachment) {
                    $currentAttachment = $this->addAttachment($newMediaId)
                                              ->toAttachmentCollection($collectionName);
                }

                $currentAttachment->order_column = $orderColumn++;
                $currentAttachment->save();

                return $currentAttachment;
            });
    }

    /**
     * Remove attachment media not in the array
     *
     * @param array $newMediaArray
     * @param string $collectionName
     */
    protected function removeAttachmentMediaNotPresentInArray(array $newMediaArray, string $collectionName = 'default')
    {
        $this->getAttachments($collectionName)
             ->reject(function (Attachment $currentAttachmentItem) use ($newMediaArray) {
                 return in_array($currentAttachmentItem->media_id, $newMediaArray);
             })
            ->each->delete();
    }

    /**
     * Attach a media to the model.
     *
     * @param string|Media $media
     *
     * @return MediaAdder
     */
    public function addAttachment($media)
    {
        return app(MediaAdderFactory::class)->create($this, $media);
    }

    /**
     * Remove all attachments in the given collection except some.
     *
     * @param string $collectionName
     * @param Attachment[]|Collection $excludedAttachments
     *
     * @return $this
     */
    public function clearAttachmentCollectionExcept(string $collectionName = 'default', $excludedAttachments = [])
    {
        if ($excludedAttachments instanceof Attachment) {
            $excludedAttachments = collect()->push($excludedAttachments);
        }

        $excludedAttachments = collect($excludedAttachments);

        if ($excludedAttachments->isEmpty()) {
            return $this->clearAttachmentCollection($collectionName);
        }

        $this->getAttachments($collectionName)
             ->reject(function (Attachment $attachment) use ($excludedAttachments) {
                 return $excludedAttachments->where('id', $attachment->id)->count();
             })
            ->each->delete();

        if ($this->attachmentsIsPreloaded()) {
            unset($this->attachment);
        }

        return $this;
    }

    /**
     * Remove all attachments in the given collection.
     *
     * @param string $collectionName
     *
     * @return $this
     */
    public function clearAttachmentCollection(string $collectionName = 'default'): self
    {
        $this->getAttachments($collectionName)
            ->each->delete();

        event(new AttachmentCollectionHasBeenCleared($this, $collectionName));

        if ($this->attachmentsIsPreloaded()) {
            unset($this->attachments);
        }

        return $this;
    }

    /**
     * Check if attachments were preloaded
     *
     * @return bool
     */
    protected function attachmentsIsPreloaded(): bool
    {
        return $this->relationLoaded('attachments');
    }

    /**
     * Delete the associated attachment with the given id.
     * You may also pass a attachment object.
     *
     * @param int|Attachment $attachmentId
     *
     * @throws AttachmentCannotBeDeleted
     */
    public function deleteAttachment($attachmentId)
    {
        if ($attachmentId instanceof Attachment) {
            $attachmentId = $attachmentId->id;
        }

        $attachment = $this->attachments->find($attachmentId);

        if (! $attachment) {
            throw AttachmentCannotBeDeleted::doesNotBelongToModel($attachmentId, $this);
        }

        $attachment->delete();
    }

    /*
     * Add a conversion.
     */
    public function addAttachmentConversion(string $name): Conversion
    {
        $conversion = Conversion::create($name);

        $this->attachmentConversions[] = $conversion;

        return $conversion;
    }

    /**
     * Add attachment collection
     *
     * @param string $name
     * @return MediaCollection
     */
    public function addAttachmentCollection(string $name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->attachmentCollections[] = $mediaCollection;

        return $mediaCollection;
    }

    /**
     * Cache the attachments on the object.
     *
     * @param string $collectionName
     *
     * @return mixed
     */
    public function loadAttachments(string $collectionName)
    {
        $collection = $this->exists
            ? $this->attachments()->with('media')->get()
            : collect($this->unAttachedAttachmentItems)->pluck('attachments');

        return $collection
            ->filter(function (Attachment $attachmentItem) use ($collectionName) {
                if ($collectionName == '') {
                    return true;
                }

                return $attachmentItem->collection_name === $collectionName;
            })
            ->sortBy('order_column')
            ->values();
    }

    /**
     * Prepare to attach
     *
     * @param Attachment $attachment
     * @param MediaAdder $mediaAdder
     */
    public function prepareToAttachAttachments(Attachment $attachment, MediaAdder $mediaAdder)
    {
        $this->unAttachedAttachmentItems[] = compact('attachment', 'mediaAdder');
    }

    /**
     * Process unattached attachments
     *
     * @param callable $callable
     */
    public function processUnattachedAttachments(callable $callable)
    {
        foreach ($this->unAttachedAttachmentItems as $item) {
            $callable($item['attachment'], $item['mediaAdder']);
        }

        $this->unAttachedAttachmentItems = [];
    }

    /**
     * Register all attachment conversions
     * @param Media|null $media
     */
    public function registerAllAttachmentConversions(Media $media = null)
    {
        $this->registerAttachmentCollections();

        collect($this->attachmentCollections)->each(function (MediaCollection $mediaCollection) use ($media) {
            $actualAttachmentConversions = $this->attachmentConversions;

            $this->attachmentConversions = [];

            ($mediaCollection->mediaConversionRegistrations)($media);

            $preparedAttachmentConversions = collect($this->attachmentConversions)
                ->each(function (Conversion $conversion) use ($mediaCollection) {
                    $conversion->performOnCollections($mediaCollection->name);
                })
                ->values()
                ->toArray();

            $this->attachmentConversions = array_merge($actualAttachmentConversions, $preparedAttachmentConversions);
        });

        $this->registerAttachmentConversions($media);
    }

    /**
     * Register attachment media collections
     */
    public function registerAttachmentCollections()
    {
    }

    /**
     * Register attachment media conversions
     *
     * @param Media|null $media
     */
    public function registerAttachmentConversions(Media $media = null)
    {
    }

    /**
     * Updates the attachment collection with given media from request
     *
     * @param $collection
     * @param Request $request
     * @param string $key the attachment field in the request
     * @return mixed
     */
    public function updateSingleAttachment($collection, Request $request, $key = '')
    {
        if (! $key) {
            $key = $collection;
        }

        $media_id = $request->input($key);
        $clear = $request->input('clear_file');
        $response = false;

        if ($request->input($key) || $clear) {
            if ($media_id) {
                // check if it's the same attachment
                $current_media = $this->getFirstAttachmentMedia($collection);

                // attach only if different
                if (empty($current_media) || $current_media->id != $media_id) {
                    $response = $this->addAttachment($media_id)
                                     ->toAttachmentCollection($collection);
                }
            } else {
                // clear
                $this->clearAttachmentCollection($collection);
                $clear = true;
                $response = 0;
            }
        }

        return $response;
    }

    /**
     * Get the first attachment media
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Media
     */
    public function getFirstAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Media
    {
        $attachment = $this->getFirstAttachment($collectionName, $filters);

        return $attachment ? $attachment->media : null;
    }

    /**
     * With attachments scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeWithAttachments($query)
    {
        return $query->with('attachments.media');
    }

    /**
     * Validate media mimetype
     *
     * @param Media $media
     * @param array ...$allowedMimeTypes
     * @throws MediaMimeTypeNotAllowed
     */
    protected function guardAgainstInvalidMediaMimeType(Media $media, ...$allowedMimeTypes)
    {
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        if (empty($allowedMimeTypes)) {
            return;
        }

        $validation = Validator::make(
            ['mimetype' => $media->mime_type],
            ['mimetype' => 'string|in:' . implode(',', $allowedMimeTypes)]
        );

        if ($validation->fails()) {
            throw MediaMimeTypeNotAllowed::create($media, $allowedMimeTypes);
        }
    }
}





