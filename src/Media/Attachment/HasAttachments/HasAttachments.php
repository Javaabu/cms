<?php

namespace Javaabu\Cms\Media\Attachment\HasAttachments;

use DateTimeInterface;
use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Javaabu\Cms\Media\Attachment\MediaAdder\MediaAdder;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Cms\Media\Attachment\Exceptions\AttachmentCannotBeDeleted;

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
     * @param string|Media $media
     *
     * @return MediaAdder
     */
    public function addAttachment($media);

    /**
     * Add a media from a request.
     *
     * @param string $key
     *
     * @return MediaAdder
     */
    public function addAttachmentFromRequest(string $key);

    /**
     * Add multiple medias from a request by keys.
     *
     * @param string[] $keys
     *
     * @return MediaAdder[]
     */
    public function addMultipleAttachmentsFromRequest(array $keys);

    /**
     * Determine if there is media in the given attachment collection.
     *
     * @param $collectionAttachment
     *
     * @return bool
     */
    public function hasAttachments(string $collectionAttachment = ''): bool;

    /**
     * Get attachment collection by its collectionName.
     *
     * @param string $collectionName
     * @param array|callable $filters
     *
     * @return Collection
     */
    public function getAttachments(string $collectionName = 'default', $filters = []);

    /**
     * Get the attachment media
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Collection
     */
    public function getAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Collection;

    /**
     * Get the first attachment
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Attachment
     */
    public function getFirstAttachment(string $collectionName = 'default', array $filters = []): ?Attachment;

    /**
     * Get the first attachment media
     *
     * @param string $collectionName
     * @param array $filters
     * @return null|Media
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
     * @param array $newAttachmentsArray
     * @param string $collectionName
     *
     * @return Collection
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachments(array $newAttachmentsArray, string $collectionName = 'default'): Collection;

    /**
     * Remove all attachments in the given collection.
     *
     * @param string $collectionName
     */
    public function clearAttachmentCollection(string $collectionName = 'default');

    /**
     * Remove all attachments in the given collection except some.
     *
     * @param string $collectionName
     * @param Attachment[]|Collection $excludedAttachments
     *
     * @return string $collectionName
     */
    public function clearAttachmentCollectionExcept(string $collectionName = 'default', $excludedAttachments = []);

    /**
     * Delete the associated attachment with the given id.
     * You may also pass a attachment object.
     *
     * @param int|Attachment $attachmentId
     *
     * @throws AttachmentCannotBeDeleted
     */
    public function deleteAttachment($attachmentId);

    /**
     * Cache the attachments on the object.
     *
     * @param string $collectionName
     *
     * @return mixed
     */
    public function loadAttachments(string $collectionName);

    /**
     * Prepare to attach
     *
     * @param Attachment $attachment
     * @param MediaAdder $mediaAdder
     */
    public function prepareToAttachAttachments(Attachment $attachment, MediaAdder $mediaAdder);

    /**
     * Process unattached attachments
     *
     * @param callable $callable
     */
    public function processUnattachedAttachments(callable $callable);

    /*
     * Add a conversion.
     */
    public function addAttachmentConversion(string $name): Conversion;

    /*
     * Register the attachment conversions.
     */
    public function registerAttachmentConversions(Media $media = null);

    /*
     * Register the attachment collections.
     */
    public function registerAttachmentCollections();

    /*
     * Register the attachment conversions and conversions set in attachment collections.
     */
    public function registerAllAttachmentConversions(Media $media = null);

    /**
     * Updates the attachment collection with given media from request
     *
     * @param $collection
     * @param Request $request
     * @param string $key the attachment field in the request
     * @return mixed
     */
    public function updateSingleAttachment($collection, Request $request, $key = '');

    /**
     * With attachments scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeWithAttachments($query);
}





