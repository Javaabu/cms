<?php

namespace Javaabu\Cms\Media\Attachment\Events;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\Conversions\Conversion;

class AttachmentConversionWillStart
{
    use SerializesModels;

    /** @var Attachment */
    public $attachment;

    /** @var Conversion */
    public $conversion;

    /** @var string */
    public $copiedOriginalFile;

    public function __construct(Attachment $attachment, Conversion $conversion, string $copiedOriginalFile)
    {
        $this->attachment = $attachment;

        $this->conversion = $conversion;

        $this->copiedOriginalFile = $copiedOriginalFile;
    }
}





