<?php

namespace Javaabu\Cms\Media\Attachment\Jobs;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Javaabu\Cms\Media\Attachment\MediaManipulator;
use Spatie\MediaLibrary\Conversions\ConversionCollection;

class PerformAttachmentConversions implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

    /** @var ConversionCollection */
    protected $conversions;

    /** @var Attachment */
    protected $attachment;

    public function __construct(ConversionCollection $conversions, Attachment $attachment)
    {
        $this->conversions
            = $conversions;

        $this->attachment = $attachment;
    }

    public function handle(): bool
    {
        app(MediaManipulator::class)->performAttachmentConversions($this->conversions, $this->attachment);

        return true;
    }
}





