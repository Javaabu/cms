<?php

namespace Javaabu\Cms\Enums;

use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;

enum PageStyles: string implements IsEnum
{
    use NativeEnumsTrait;

    case FULLWIDTH = 'fullwidth';
    case SIDEBAR = 'sidebar';

    /**
     * Initialize labels
     */
    public static function labels(): array
    {
        return [
            self::FULLWIDTH->value => __('Fullwidth'),
            self::SIDEBAR->value   => __('Sidebar'),
        ];
    }
}
