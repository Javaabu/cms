<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Helpers\Enums\NativeEnumsTrait;

enum PostTypeFeatures: string
{
    use NativeEnumsTrait;

    case DOCUMENTS = 'documents';
    case IMAGE_GALLERY = 'image-gallery';
    case DOCUMENT_NUMBER = 'document-number';
    case EXPIREABLE = 'expireable';
    case VIDEO_LINK = 'video-link';
    case FORMAT = 'format';
    case RELATED_GALLERIES = 'related-galleries';
    case CATEGORIES = 'categories';
    case PAGE_STYLE = 'page-style';
    case ROOT_PAGE = 'root-page';
    case REF_NO = 'reference-no';
    case REDIRECT_URL = 'redirect-url';
    case GAZETTE_LINK = 'gazette-link';

    /**
     * Get the label for the enum case
     */
    public function label(): string
    {
        return match ($this) {
            self::DOCUMENTS         => __('Documents'),
            self::IMAGE_GALLERY     => __('Image Gallery'),
            self::DOCUMENT_NUMBER   => __('Document Number'),
            self::EXPIREABLE        => __('Expires At'),
            self::VIDEO_LINK        => __('Video Link'),
            self::FORMAT            => __('Format'),
            self::RELATED_GALLERIES => __('Related Galleries'),
            self::CATEGORIES        => __('Categories'),
            self::PAGE_STYLE        => __('Page Style'),
            self::ROOT_PAGE         => __('Root Page'),
            self::REF_NO            => __('Reference No.'),
            self::REDIRECT_URL      => __('Redirect URL'),
            self::GAZETTE_LINK      => __('Gazette Link'),
        };
    }

    /**
     * Get the collection name for the case
     */
    public function getCollectionName(bool $translated = false): string
    {
        $collectionName = match ($this) {
            self::DOCUMENTS      => 'documents',
            self::IMAGE_GALLERY,
            self::FORMAT         => 'image_gallery',
            default              => '',
        };

        if ($collectionName && $translated) {
            $collectionName .= '_translated';
        }

        return $collectionName;
    }

    /**
     * Get dummy data for the case
     */
    public function getDummyData(): string
    {
        $data = match ($this) {
            self::DOCUMENTS         => 'https://i.imgur.com/KDezgrt.jpg',
            self::IMAGE_GALLERY     => 'https://i.imgur.com/KDezgrt.jpg,https://i.imgur.com/kyvxGUG.jpg',
            self::DOCUMENT_NUMBER   => 'ABC/12-2019',
            self::EXPIREABLE        => '19/11/2021',
            self::VIDEO_LINK        => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            self::FORMAT            => 'photo',
            self::RELATED_GALLERIES => 'office-gallery',
            self::CATEGORIES        => 'agreement-signing,category-x',
            default                 => '',
        };

        return $data ? trans($data) : '';
    }

    /**
     * Convert human-readable label to enum instance
     * * @param string $label
     * @return self
     * @throws \ValueError
     */
    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if (strtolower(trim($label)) === strtolower($case->label())) {
                return $case;
            }
        }

        throw new \ValueError("$label is not a valid backing label for enum " . self::class);
    }
}
