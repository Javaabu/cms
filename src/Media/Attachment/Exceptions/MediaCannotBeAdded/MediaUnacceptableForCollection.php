<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;

class MediaUnacceptableForCollection extends MediaCannotBeAdded
{
    public static function create(Media $media, MediaCollection $mediaCollection, HasAttachments $hasAttachments)
    {
        $modelType = get_class($hasAttachments);

        return new static("The media with the id `{$media->id}` was not accepted into the collection named `{$mediaCollection->name}` of model `{$modelType}` with id `{$hasAttachments->getKey()}`");
    }
}





