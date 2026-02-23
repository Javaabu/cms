<?php

namespace Javaabu\Cms\Media\Attachment\Events;

use Illuminate\Queue\SerializesModels;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;

class AttachmentCollectionHasBeenCleared
{
    use SerializesModels;

    /** @var HasAttachments */
    public $model;

    /** @var string */
    public $collectionName;

    public function __construct(HasAttachments $model, string $collectionName)
    {
        $this->model = $model;

        $this->collectionName = $collectionName;
    }
}





