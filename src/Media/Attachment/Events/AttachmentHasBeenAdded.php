<?php

namespace Javaabu\Cms\Media\Attachment\Events;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Queue\SerializesModels;

class AttachmentHasBeenAdded
{
    use SerializesModels;

    /** @var Attachment */
    public $attachment;

    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }
}





