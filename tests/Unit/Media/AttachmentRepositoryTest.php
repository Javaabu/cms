<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Illuminate\Support\Collection;
use Javaabu\Cms\Media\Attachment\Attachment;
use Javaabu\Cms\Media\Attachment\AttachmentRepository;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class AttachmentRepositoryTest extends TestCase
{
    #[Test]
    public function it_filters_attachment_collections_by_nested_media_custom_properties(): void
    {
        $matchingAttachment = $this->attachmentWithMediaProperties([
            'gallery' => [
                'slot' => 'hero',
            ],
            'locale' => 'en',
        ]);

        $otherAttachment = $this->attachmentWithMediaProperties([
            'gallery' => [
                'slot' => 'body',
            ],
            'locale' => 'dv',
        ]);

        $model = Mockery::mock(HasAttachments::class);
        $model->shouldReceive('loadAttachments')
            ->once()
            ->with('images')
            ->andReturn(collect([$matchingAttachment, $otherAttachment]));

        $attachments = (new AttachmentRepository(new Attachment))
            ->getCollection($model, 'images', ['gallery.slot' => 'hero', 'locale' => 'en']);

        $this->assertCount(1, $attachments);
        $this->assertTrue($attachments->first()->is($matchingAttachment));
    }

    #[Test]
    public function it_accepts_callable_filters_for_attachment_collections(): void
    {
        $firstAttachment = new Attachment;
        $firstAttachment->collection_name = 'documents';
        $firstAttachment->id = 1;
        $secondAttachment = new Attachment;
        $secondAttachment->collection_name = 'documents';
        $secondAttachment->id = 2;

        $model = Mockery::mock(HasAttachments::class);
        $model->shouldReceive('loadAttachments')
            ->once()
            ->with('documents')
            ->andReturn(new Collection([$firstAttachment, $secondAttachment]));

        $attachments = (new AttachmentRepository(new Attachment))
            ->getCollection($model, 'documents', fn (Attachment $attachment) => $attachment->id === 2);

        $this->assertSame([2], $attachments->pluck('id')->all());
    }

    private function attachmentWithMediaProperties(array $customProperties): Attachment
    {
        $media = new Media;
        $media->custom_properties = $customProperties;

        $attachment = new Attachment;
        $attachment->collection_name = 'images';
        $attachment->setRelation('media', $media);

        return $attachment;
    }
}
