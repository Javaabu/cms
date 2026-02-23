<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

class MediaMimeTypeNotAllowed extends MediaCannotBeAdded
{
    public static function create(Media $media, array $allowedMimeTypes)
    {
        $mimeType = $media->mime_type;

        $allowedMimeTypes = implode(', ', $allowedMimeTypes);

        return new static("Media has a mimetype of {$mimeType}, while only {$allowedMimeTypes} are allowed");
    }
}





