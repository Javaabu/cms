<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Javaabu\Cms\Enums\PostStatus;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostStatusTest extends TestCase
{
    #[Test]
    public function it_exposes_labels_colors_and_boolean_helpers_for_each_status(): void
    {
        $this->assertSame('Draft', PostStatus::DRAFT->label());
        $this->assertSame('Published', PostStatus::PUBLISHED->label());
        $this->assertSame('Scheduled', PostStatus::SCHEDULED->label());
        $this->assertSame('Pending Review', PostStatus::PENDING->label());
        $this->assertSame('Rejected', PostStatus::REJECTED->label());
        $this->assertSame('Archived', PostStatus::ARCHIVED->label());

        $this->assertSame('secondary', PostStatus::DRAFT->color());
        $this->assertSame('success', PostStatus::PUBLISHED->color());
        $this->assertSame('info', PostStatus::SCHEDULED->color());
        $this->assertSame('warning', PostStatus::PENDING->color());
        $this->assertSame('danger', PostStatus::REJECTED->color());
        $this->assertSame('dark', PostStatus::ARCHIVED->color());

        $this->assertTrue(PostStatus::PUBLISHED->isPublished());
        $this->assertFalse(PostStatus::DRAFT->isPublished());
        $this->assertTrue(PostStatus::DRAFT->isDraft());
        $this->assertFalse(PostStatus::PUBLISHED->isDraft());
    }

    #[Test]
    public function it_returns_status_values_and_labels_in_case_order(): void
    {
        $this->assertSame(
            ['draft', 'published', 'scheduled', 'pending', 'rejected', 'archived'],
            PostStatus::values()
        );

        $this->assertSame(
            ['Draft', 'Published', 'Scheduled', 'Pending Review', 'Rejected', 'Archived'],
            PostStatus::labels()
        );
    }
}

