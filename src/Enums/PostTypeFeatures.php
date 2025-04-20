<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Helpers\Traits\EnumsTrait;

abstract class PostTypeFeatures
{
    use EnumsTrait;

    const DOCUMENTS = 'documents';
    const IMAGE_GALLERY = 'image-gallery';
    const DOCUMENT_NUMBER = 'document-number';
    const EXPIREABLE = 'expireable';
    const VIDEO_LINK = 'video-link';
    const FORMAT = 'format';
    const RELATED_GALLERIES = 'related-galleries';
    const CATEGORIES = 'categories';
    const PAGE_STYLE = 'page-style';
    const ROOT_PAGE = 'root-page';
    const REF_NO = 'reference-no';
    const REDIRECT_URL = 'redirect-url';
    const COORDS = 'coords';
    const CITY = 'city';

    protected static array $feature_collection_names = [];
    /**
     * Slugs
     */
    protected static array $slugs = [
        self::DOCUMENTS         => 'documents',
        self::IMAGE_GALLERY     => 'image-gallery',
        self::DOCUMENT_NUMBER   => 'document-number',
        self::EXPIREABLE        => 'expireable',
        self::VIDEO_LINK        => 'video-link',
        self::FORMAT            => 'format',
        self::RELATED_GALLERIES => 'related-galleries',
        self::CATEGORIES        => 'categories',
        self::PAGE_STYLE        => 'page-style',
        self::ROOT_PAGE         => 'root-page',
        self::REF_NO            => 'reference-no',
        self::REDIRECT_URL      => 'redirect-url',
        self::COORDS            => 'coords',
    ];

    protected static array $dummyData;

//    public static function getTranslatedCollectionName()
//    {
//
//    }

    public static function getLabel($key): string
    {
        if (isset(static::getLabels()[$key])) {
            if (auth()->guard()->name == 'web_admin') {
                return static::getLabels()[$key];
            } else {
                return trans(static::getLabels()[$key]);
            }
        }

        return '';
    }

    /**
     * Get collection for key
     *
     * @param string $key
     * @param bool $translated
     * @return string
     */
    public static function getCollectionName(string $key, bool $translated = false): string
    {
        $collectionName = '';

        if (isset(static::getCollectionNames()[$key])) {
            $collectionName = static::getCollectionNames()[$key];
        }

        if ($translated) {
            $collectionName .= '_translated';
        }

        return $collectionName;
    }

    /**
     * Get type labels
     *
     * @return array
     */
    public static function getCollectionNames(): array
    {
        //first initialize
        if (empty(static::$feature_collection_names)) {
            static::initFeatureCollectionNames();
        }

        return static::$feature_collection_names;
    }

    /**
     * Initialize labels
     */
    protected static function initFeatureCollectionNames()
    {
        static::$feature_collection_names = [
            static::DOCUMENTS     => 'documents',
            static::IMAGE_GALLERY => 'image_gallery',
            static::FORMAT        => 'image_gallery',
        ];
    }

    /**
     * Get dummyData for key
     *
     * @param $key
     * @return string
     */
    public static function getDummyData($key): string
    {
        return isset(static::getDummyDatas()[$key]) ? trans(static::getDummyDatas()[$key]) : '';
    }

    /**
     * Get type dummyData
     *
     * @return array
     */
    public static function getDummyDatas(): array
    {
        //first initialize
        if (empty(static::$dummyData)) {
            static::initDummyData();
        }

        return static::$dummyData;
    }

    /**
     * Initialize labels
     */
    protected static function initDummyData()
    {
        static::$dummyData = [
            static::DOCUMENTS         => 'https://i.imgur.com/KDezgrt.jpg',
            static::IMAGE_GALLERY     => 'https://i.imgur.com/KDezgrt.jpg,https://i.imgur.com/kyvxGUG.jpg',
            static::DOCUMENT_NUMBER   => 'ABC/12-2019',
            static::EXPIREABLE        => '19/11/2021',
            static::VIDEO_LINK        => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            static::FORMAT            => 'photo',
            static::RELATED_GALLERIES => 'office-gallery',
            self::CATEGORIES          => 'agreement-signing,category-x',
            static::PAGE_STYLE        => '',
            static::ROOT_PAGE         => '',
            static::REF_NO            => '',
            static::REDIRECT_URL      => '',
            self::COORDS              => '',
        ];
    }

    /**
     * Initialize labels
     */
    protected static function initLabels()
    {
        static::$labels = [
            static::DOCUMENTS         => _d('Documents'),
            static::IMAGE_GALLERY     => _d('Image Gallery'),
            static::DOCUMENT_NUMBER   => _d('Document Number'),
            static::EXPIREABLE        => _d('Expires At'),
            static::VIDEO_LINK        => _d('Video Link'),
            static::FORMAT            => _d('Format'),
            static::RELATED_GALLERIES => _d('Related Galleries'),
            self::CATEGORIES          => _d('Categories'),
            static::PAGE_STYLE        => _d('Page Style'),
            static::ROOT_PAGE         => _d('Root Page'),
            static::REF_NO            => _d('Reference No.'),
            static::REDIRECT_URL      => _d('Redirect URL'),
            static::COORDS            => _d('Coordinates'),
        ];
    }
}
