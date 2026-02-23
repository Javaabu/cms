<?php
/**
 * Custom path generator for media library
 */

namespace Javaabu\Cms\Media;

use Vinkla\Hashids\Facades\Hashids;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'c/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.

     * @return string
     */

    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    /*
     * Get the path for responsive images of the given media, relative to the root storage path.

     * @return string
     */

    protected function getBasePath(Media $media): string
    {
        return Hashids::connection('uploads')
                      ->encode($media->getKey());
    }

    /*
     * Get a (unique) base path for the given media.
     */

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'cri/';
    }
}





