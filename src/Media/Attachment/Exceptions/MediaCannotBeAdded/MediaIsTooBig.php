<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

use Spatie\MediaLibrary\Support\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

class MediaIsTooBig extends MediaCannotBeAdded
{
    public static function create(Media $media)
    {
        $fileSize = $media->human_readable_size;

        $maxFileSize = File::getHumanReadableSize(config('media-library.max_file_size'));

        return new static("Media `{$media->id}` has a size of {$fileSize} which is greater than the maximum allowed {$maxFileSize}");
    }
}





