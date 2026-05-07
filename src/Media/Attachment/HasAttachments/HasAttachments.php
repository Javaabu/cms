<?php

namespace Javaabu\Cms\Media\Attachment\HasAttachments;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Javaabu\Cms\Media\Attachment\Attachment;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeDeleted;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Cms\Media\Attachment\MediaAdder\MediaAdder;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface HasAttachments
{
    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function attachments();

    /**
     * Attach a media to the model.
     *
     * @param  string|Media  $media
     * @return MediaAdder
     */
    public function addAttachment($media);

    /**
     * Add a media from a request.
     *
     *
     * @return MediaAdder
     */
    public function addAttachmentFromRequest(string $key);

    /**
     * Add multiple medias from a request by keys.
     *
     * @param  string[]  $keys
     * @return MediaAdder[]
     */
    public function addMultipleAttachmentsFromRequest(array $keys);

    /**
     * Determine if there is media in the given attachment collection.
     */
    public function hasAttachments(string $collectionAttachment = ''): bool;

    /**
     * Get attachment collection by its collectionName.
     *
     * @param  array|callable  $filters
     * @return Collection
     */
    public function getAttachments(string $collectionName = 'default', $filters = []);

    /**
     * Get the attachment media
     */
    public function getAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Collection;

    /**
     * Get the first attachment
     */
    public function getFirstAttachment(string $collectionName = 'default', array $filters = []): ?Attachment;

    /**
     * Get the first attachment media
     */
    public function getFirstAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Media;

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */
    public function getFirstAttachmentUrl(string $collectionName = 'default', string $conversionName = ''): string;

    /*
     * Get the url of the image for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */
    public function getFirstAttachmentTemporaryUrl(DateTimeInterface $expiration, string $collectionName = 'default', string $conversionName = ''): string;

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */
    public function getFirstAttachmentPath(string $collectionName = 'default', string $conversionName = ''): string;

    /**
     * Update an attachment collection by deleting and inserting again with new values.
     *
     *
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachments(array $newAttachmentsArray, string $collectionName = 'default'): Collection;

    /**
     * Remove all attachments in the given collection.
     */
    public function clearAttachmentCollection(string $collectionName = 'default');

    /**
     * Remove all attachments in the given collection except some.
     *
     * @param  Attachment[]|Collection  $excludedAttachments
     * @return string $collectionName
     */
    public function clearAttachmentCollectionExcept(string $collectionName = 'default', $excludedAttachments = []);

    /**
     * Delete the associated attachment with the given id.
     * You may also pass a attachment object.
     *
     * @param  int|Attachment  $attachmentId
     *
     * @throws AttachmentCannotBeDeleted
     */
    public function deleteAttachment($attachmentId);

    /**
     * Cache the attachments on the object.
     *
     *
     * @return mixed
     */
    public function loadAttachments(string $collectionName);

    /**
     * Prepare to attach
     */
    public function prepareToAttachAttachments(Attachment $attachment, MediaAdder $mediaAdder);

    /**
     * Process unattached attachments
     */
    public function processUnattachedAttachments(callable $callable);

    /*
     * Add a conversion.
     */
    public function addAttachmentConversion(string $name): Conversion;

    /*
     * Register the attachment conversions.
     */
    public function registerAttachmentConversions(?Media $media = null);

    /*
     * Register the attachment collections.
     */
    public function registerAttachmentCollections();

    /*
     * Register the attachment conversions and conversions set in attachment collections.
     */
    public function registerAllAttachmentConversions(?Media $media = null);

    /**
     * Updates the attachment collection with given media from request
     *
     * @param  string  $key  the attachment field in the request
     * @return mixed
     */
    public function updateSingleAttachment($collection, Request $request, $key = '');

    /**
     * With attachments scope
     *
     * @return mixed
     */
    public function scopeWithAttachments($query);
}
