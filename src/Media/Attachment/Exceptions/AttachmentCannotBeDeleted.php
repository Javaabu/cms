<?php

namespace Javaabu\Cms\Media\Attachment\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class AttachmentCannotBeDeleted extends Exception
{
    public static function doesNotBelongToModel($attachmentId, Model $model)
    {
        $modelClass = get_class($model);

        return new static("Attachment with id `{$attachmentId}` cannot be deleted because it does not exist or does not belong to model {$modelClass} with id {$model->id}");
    }
}





