<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;


use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

class MediaDoesNotExist extends MediaCannotBeAdded
{
    public static function create(string $id)
    {
        return new static("Media `{$id}` does not exist");
    }
}





