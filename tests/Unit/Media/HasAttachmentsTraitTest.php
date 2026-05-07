<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Cms\Media\Attachment\Attachment;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachmentsTrait;
use Javaabu\Cms\Media\Attachment\MediaAdder\MediaAdder;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasAttachmentsTraitTest extends TestCase
{
    #[Test]
    public function it_loads_pending_attachments_for_models_that_have_not_been_saved_yet(): void
    {
        $model = new PendingAttachmentModel;

        $first = new Attachment;
        $first->collection_name = 'images';
        $first->order_column = 2;

        $second = new Attachment;
        $second->collection_name = 'images';
        $second->order_column = 1;

        $document = new Attachment;
        $document->collection_name = 'documents';
        $document->order_column = 1;

        $model->prepareToAttachAttachments($first, new MediaAdder);
        $model->prepareToAttachAttachments($second, new MediaAdder);
        $model->prepareToAttachAttachments($document, new MediaAdder);

        $attachments = $model->loadAttachments('images');

        $this->assertCount(2, $attachments);
        $this->assertSame([$second, $first], $attachments->all());
    }
}

class PendingAttachmentModel extends Model implements HasAttachments
{
    use HasAttachmentsTrait;
}
