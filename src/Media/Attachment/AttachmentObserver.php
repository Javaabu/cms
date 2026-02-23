<?php

namespace Javaabu\Cms\Media\Attachment;

use Javaabu\Cms\Media\Attachment\Attachment;

class AttachmentObserver
{
    /**
     * Creating event
     *
     * @param Attachment $attachment
     */
    public function creating(Attachment $attachment)
    {
        if ($attachment->shouldSortWhenCreating()) {
            $attachment->setHighestOrderNumber();
        }
    }

    /**
     * Creating event
     *
     * @param Attachment $attachment
     */
    /*public function updating(Media $media)
    {
        if ($media->file_name !== $media->getOriginal('file_name')) {
            app(Filesystem::class)->syncFileNames($media);
        }
    }*/

    /**
     * Generate the conversions
     *
     * @param Attachment $attachment
     */
    public function created(Attachment $attachment)
    {
        if (is_null($attachment->model_id)) {
            return;
        }

        $eventDispatcher = Attachment::getEventDispatcher();
        Attachment::unsetEventDispatcher();

        app(MediaManipulator::class)->createDerivedAttachmentFiles($attachment);

        Attachment::setEventDispatcher($eventDispatcher);
    }

    /*public function deleted(Media $media)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($media))) {
            if (! $media->isForceDeleting()) {
                return;
            }
        }

        app(Filesystem::class)->removeAllFiles($media);
    }*/
}





