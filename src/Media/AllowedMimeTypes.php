<?php

namespace Javaabu\Cms\Media;

abstract class AllowedMimeTypes extends \Javaabu\Helpers\Media\AllowedMimeTypes
{
    protected static bool $cmsMimeTypesRegistered = false;

    public static function registerCmsMimeTypes(): void
    {
        if (static::$cmsMimeTypesRegistered) {
            return;
        }

        parent::registerMimeTypes('image', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/tiff',
            'image/x-citrix-png',
            'image/x-png',
            'image/svg',
            'image/svg+xml',
            'image/webp',
            'application/octet-stream',
            'application/json',
        ]);

        parent::registerMimeTypes('document', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/tiff',
            'image/x-citrix-png',
            'image/x-png',
            'image/svg',
            'image/svg+xml',
            'application/pdf',
            'application/msword',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        ]);

        parent::registerMimeTypes('archive', [
            'application/zip',
            'application/octet-stream',
            'application/x-zip-compressed',
            'multipart/x-zip',
        ]);

        parent::registerMimeTypes('spreadsheet', [
            'text/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        ]);

        parent::registerIcons('material', [
            'image' => 'file-image',
            'document' => 'file-text',
            'archive' => 'file-archive',
            'spreadsheet' => 'file-excel',
        ]);

        parent::registerIcons('fontawesome', [
            'document' => 'file-text',
            'archive' => 'file-archive',
            'spreadsheet' => 'file-excel',
        ]);

        static::$cmsMimeTypesRegistered = true;
    }

    public static function getAllowedTypes()
    {
        static::registerCmsMimeTypes();

        return parent::getAllowedTypes();
    }

    public static function getAllowedMimeTypes(string|array $type = ''): array
    {
        static::registerCmsMimeTypes();

        return parent::getAllowedMimeTypes($type);
    }

    public static function getType(string $mime_type): ?string
    {
        static::registerCmsMimeTypes();

        return parent::getType($mime_type);
    }

    public static function getValidationRule(string|array $type = '', bool $as_array = true, ?int $max_size = null): array|string
    {
        static::registerCmsMimeTypes();

        return parent::getValidationRule($type, $as_array, $max_size);
    }

    public static function getAttachmentValidationRule(string|array|null $type = null): array
    {
        static::registerCmsMimeTypes();

        return parent::getAttachmentValidationRule($type);
    }

    public static function getAllowedMimeTypesString(string|array $type = '', string $separator = ','): string
    {
        static::registerCmsMimeTypes();

        return parent::getAllowedMimeTypesString($type, $separator);
    }

    public static function getIcon(string $mime_type, string $icon_pack = '', bool $with_prefix = false): string
    {
        static::registerCmsMimeTypes();

        return parent::getIcon($mime_type, $icon_pack ?: 'material', $with_prefix);
    }

    public static function getWebIcon(string $mime_type): string
    {
        static::registerCmsMimeTypes();

        return parent::getIcon($mime_type, 'fontawesome');
    }
}
