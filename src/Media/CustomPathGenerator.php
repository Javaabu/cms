<?php

/**
 * Custom path generator for media library
 */

namespace Javaabu\Cms\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Vinkla\Hashids\Facades\Hashids;

class CustomPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'c/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.

     * @return string
     */

    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    /*
     * Get the path for responsive images of the given media, relative to the root storage path.

     * @return string
     */

    protected function getBasePath(Media $media): string
    {
        if (class_exists(Hashids::class)) {
            return Hashids::connection('uploads')
                ->encode($media->getKey());
        }

        return (string) $media->getKey();
    }

    /*
     * Get a (unique) base path for the given media.
     */

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'cri/';
    }
}
