<?php

namespace Javaabu\Cms\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case PENDING = 'pending';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

    /**
     * Get the label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::SCHEDULED => 'Scheduled',
            self::PENDING => 'Pending Review',
            self::REJECTED => 'Rejected',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get badge color for the status
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::PUBLISHED => 'success',
            self::SCHEDULED => 'info',
            self::PENDING => 'warning',
            self::REJECTED => 'danger',
            self::ARCHIVED => 'dark',
        };
    }

    /**
     * Check if status is published
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if status is draft
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Get all status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all status labels
     */
    public static function labels(): array
    {
        return array_map(fn($case) => $case->label(), self::cases());
    }
}
