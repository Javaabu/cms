<?php

namespace Javaabu\Cms\Media\Attachment\Events;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\Conversions\Conversion;

class AttachmentConversionHasBeenCompleted
{
    use SerializesModels;

    /** @var Attachment */
    public $attachment;

    /** @var Conversion */
    public $conversion;

    public function __construct(Attachment $attachment, Conversion $conversion)
    {
        $this->attachment = $attachment;

        $this->conversion = $conversion;
    }
}





