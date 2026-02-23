<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

use Javaabu\Cms\Media\Attachment\Exceptions\MediaCannotBeAdded;

class UnknownType extends MediaCannotBeAdded
{
    public static function create()
    {
        return new static('Only ids, MediaObjects can be attached');
    }
}





