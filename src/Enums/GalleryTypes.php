<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;

enum GalleryTypes: string implements IsEnum
{
    use NativeEnumsTrait;

    case PHOTO = 'photo';
    case VIDEO = 'video';

    /**
     * Icons
     */
    protected static function icons() {
        return [
            self::PHOTO->value => 'image',
            self::VIDEO->value => 'videocam',
        ];
    }

    /**
     * Front End Icons
     */
    protected static function web_icons() {
        return [
            self::PHOTO->value => 'image',
            self::VIDEO->value => 'play',
        ];
    }

    /**
     * Get icon for key
     *
     * @param $key
     * @return string
     */
    public static function getIcon($key)
    {
        $icons = self::icons();

        return isset($icons[$key]) ? 'zmdi zmdi-' . $icons[$key] : '';
    }

    /**
     * Get web icon for key
     *
     * @param $key
     * @return string
     */
    public static function getWebIcon($key)
    {
        $web_icons = self::web_icons();

        return array_key_exists($key, $web_icons) ? 'far fa-' . $web_icons[$key] : '';
    }
}
