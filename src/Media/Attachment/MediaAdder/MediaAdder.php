<?php

namespace Javaabu\Cms\Media\Attachment\MediaAdder;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\File as PendingFile;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;
use Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded\UnknownType;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image as ImageGenerator;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded\MediaIsTooBig;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded\MediaDoesNotExist;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded\MediaUnacceptableForCollection;

class MediaAdder
{
    /** @var Model subject */
    protected $subject;

    /** @var string|Media */
    protected $media;

    /** @var bool */
    protected $generateResponsiveImages = false;

    /**
     * @param Model $subject
     *
     * @return MediaAdder
     */
    public function setSubject(Model $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /*
     * Set the media that needs to be added.
     *
     * @param string|Media $media
     *
     * @return $this
     */
    public function setMedia($media): self
    {
        if (is_numeric($media)) {
            $media = Media::whereId($media)->first();
        }

        if (! ($media instanceof Media)) {
            throw UnknownType::create();
        }

        $this->media = $media;

        return $this;
    }

    /**
     * Enable responsive images
     *
     * @return MediaAdder
     */
    public function withResponsiveImages(): self
    {
        $this->generateResponsiveImages = true;

        return $this;
    }

    /**
     * Add to attachment collection
     *
     * @param string $collectionName
     * @return Attachment
     * @throws MediaDoesNotExist
     * @throws MediaIsTooBig
     */
    public function toAttachmentCollection(string $collectionName = 'default'): Attachment
    {
        if (! ($this->media instanceof Media)) {
            throw MediaDoesNotExist::create($this->media);
        }

        if ($this->media->size > config('media-library.max_file_size')) {
            throw MediaIsTooBig::create($this->media);
        }

        $attachmentClass = Attachment::class;

        $attachment = new $attachmentClass();

        $attachment->media()->associate($this->media);

        $attachment->collection_name = $collectionName;

        $this->attachAttachment($attachment);

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     */
    protected function attachAttachment(Attachment $attachment)
    {
        if (! $this->subject->exists) {
            $this->subject->prepareToAttachAttachments($attachment, $this);

            $class = get_class($this->subject);

            $class::created(function ($model) {
                $model->processUnattachedAttachments(function (Attachment $attachment) use ($model) {
                    $this->processAttachmentItem($model, $attachment);
                });
            });

            return;
        }

        $this->processAttachmentItem($this->subject, $attachment);
    }

    /**
     * Process the attachment item
     *
     * @param HasAttachments $model
     * @param Attachment $attachment
     */
    protected function processAttachmentItem(HasAttachments $model, Attachment $attachment)
    {
        $media = $attachment->media;

        $this->guardAgainstDisallowedMediaAdditions($attachment);

        $model->attachments()->save($attachment);

        if ($this->generateResponsiveImages && (new ImageGenerator())->canConvert($media)) {
            $generateResponsiveImagesJobClass = config('media-library.jobs.generate_responsive_images', GenerateResponsiveImagesJob::class);

            $job = new $generateResponsiveImagesJobClass($media);

            if ($customQueue = config('media-library.queue_name')) {
                $job->onQueue($customQueue);
            }

            dispatch($job);
        }

        if (optional($this->getAttachmentCollection($attachment->collection_name))->singleFile) {
            $model->clearAttachmentCollectionExcept($attachment->collection_name, $attachment);
        }
    }

    /**
     * Guard against disallowed media additions
     *
     * @param Attachment $attachment
     * @throws MediaUnacceptableForCollection
     */
    protected function guardAgainstDisallowedMediaAdditions(Attachment $attachment)
    {
        $media = $attachment->media;

        $file = PendingFile::createFromMedia($media);

        if (! $collection = $this->getAttachmentCollection($attachment->collection_name)) {
            return;
        }

        if (! ($collection->acceptsFile)($file, $this->subject)) {
            throw MediaUnacceptableForCollection::create($media, $collection, $this->subject);
        }
    }

    /**
     * Get the attachment collection
     *
     * @param string $collectionName
     * @return null|MediaCollection
     */
    protected function getAttachmentCollection(string $collectionName): ?MediaCollection
    {
        $this->subject->registerAttachmentCollections();

        return collect($this->subject->attachmentCollections)
            ->first(function (MediaCollection $collection) use ($collectionName) {
                return $collection->name === $collectionName;
            });
    }
}





