<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions;

use Exception;
use Javaabu\Cms\Media\Attachment\Attachment;

class AttachmentCannotBeUpdated extends Exception
{
    public static function doesNotBelongToCollection(string $collectionName, Attachment $attachment)
    {
        return new static("Attachment id {$attachment->getKey()} is not part of collection `{$collectionName}`");
    }
}





