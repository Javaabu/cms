<?php

namespace Javaabu\Cms\Media\Attachment\Conversion;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\Conversions\Conversion;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;

class AttachmentConversionCollection extends ConversionCollection
{
    /**
     * @param Attachment $attachment
     *
     * @return static
     */
    public static function createForAttachment(Attachment $attachment)
    {
        return (new static())->setAttachment($attachment);
    }

    /**
     * @param Attachment $attachment
     *
     * @return $this
     */
    public function setAttachment(Attachment $attachment)
    {
        $media = $attachment->media;

        $this->media = $media;

        $this->items = [];

        $this->addAttachmentConversionsFromRelatedModel($attachment);

        return $this;
    }

    /**
     * Add the conversion that are defined on the related model of
     * the given media.
     *
     * @param Attachment $attachment
     */
    protected function addAttachmentConversionsFromRelatedModel(Attachment $attachment)
    {
        $media = $attachment->media;
        $modelName = Arr::get(Relation::morphMap(), $attachment->model_type, $attachment->model_type);

        /** @var HasAttachments $model */
        $model = new $modelName();

        /*
         * In some cases the user might want to get the actual model
         * instance so conversion parameters can depend on model
         * properties. This will causes extra queries.
         */
        if ($model->registerAttachmentConversionsUsingModelInstance) {
            $model = $attachment->model;

            $model->attachmentConversion = [];
        }

        $model->registerAllAttachmentConversions($media);

        $this->items = $model->attachmentConversions;
    }

    public function getQueuedConversions(string $collectionName = ''): self
    {
        return $this
            ->getConversions($collectionName)
            ->filter(fn (Conversion $conversion) => $conversion->shouldBeQueued());
    }

    public function getNonQueuedConversions(string $collectionName = ''): self
    {
        return $this
            ->getConversions($collectionName)
            ->reject(fn (Conversion $conversion) => $conversion->shouldBeQueued());
    }


}





