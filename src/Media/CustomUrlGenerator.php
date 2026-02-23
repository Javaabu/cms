<?php
/**
 * Created by PhpStorm.
 * User: Arushad
 * Date: 15/09/2016
 * Time: 22:54
 */

namespace Javaabu\Cms\Media;

use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator;

class CustomUrlGenerator extends LocalUrlGenerator
{
    /**
     * Get the url for the profile of a media item.
     *
     * @return string
     */
    public function getUrl(): string
    {
        $url = parent::getUrl();
        return url($url);
    }
}





