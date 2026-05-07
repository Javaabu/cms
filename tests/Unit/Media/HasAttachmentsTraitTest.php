<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use PHPUnit\Framework\Attributes\Test;

class HasAttachmentsTraitTest extends TestCase
{
    #[Test]
    public function cms_models_use_the_upstream_mediapicker_attachment_contract(): void
    {
        $this->assertInstanceOf(HasAttachments::class, new Post);
        $this->assertInstanceOf(HasAttachments::class, new Category);
        $this->assertTrue(method_exists(Post::class, 'updateSingleAttachment'));
        $this->assertTrue(method_exists(Category::class, 'updateSingleAttachment'));
    }
}
