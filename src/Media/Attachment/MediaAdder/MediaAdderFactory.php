<?php

namespace Javaabu\Cms\Media\Attachment\MediaAdder;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAdderFactory
{
    /**
     * Add a media from the request
     *
     * @param Model $subject
     * @param string $key
     * @return MediaAdder
     */
    public static function createFromRequest(Model $subject, string $key): MediaAdder
    {
        return static::createMultipleFromRequest($subject, [$key])->first();
    }

    /**
     * Add multiple media from the request
     *
     * @param Model $subject
     * @param array $keys
     * @return Collection
     */
    public static function createMultipleFromRequest(Model $subject, array $keys = []): Collection
    {
        return collect($keys)
            ->map(function (string $key) use ($subject) {
                $media = request()->input($key);

                if (! is_array($media)) {
                    return static::create($subject, $media);
                }

                return array_map(function ($media_object) use ($subject) {
                    return static::create($subject, $media_object);
                }, $media);
            })
            ->flatten();
    }

    /**
     * @param Model $subject
     * @param string|Media $media
     *
     * @return MediaAdder
     */
    public static function create(Model $subject, $media)
    {
        return app(MediaAdder::class)
            ->setSubject($subject)
            ->setMedia($media);
    }
}





