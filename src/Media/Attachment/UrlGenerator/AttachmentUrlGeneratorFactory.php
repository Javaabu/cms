<?php

namespace Javaabu\Cms\Media\Attachment\UrlGenerator;

use Javaabu\Cms\Media\Attachment\Attachment;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGenerator;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGeneratorFactory;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;
use Javaabu\Cms\Media\Attachment\Conversion\AttachmentConversionCollection;

class AttachmentUrlGeneratorFactory extends UrlGeneratorFactory
{
    public static function createForAttachment(Attachment $attachment, string $conversionName = ''): UrlGenerator
    {
        $media = $attachment->media;

        $urlGeneratorClass = config('media-library.url_generator')
            ?: 'Spatie\MediaLibrary\UrlGenerator\\' . ucfirst($media->getDiskDriverName()) . 'UrlGenerator';

        static::guardAgainstInvalidUrlGenerator($urlGeneratorClass);

        $urlGenerator = app($urlGeneratorClass);
        $pathGenerator = PathGeneratorFactory::create($media);

        $urlGenerator
            ->setMedia($media)
            ->setPathGenerator($pathGenerator);

        if ($conversionName !== '') {
            $conversion = AttachmentConversionCollection::createForAttachment($attachment)->getByName($conversionName);

            $urlGenerator->setConversion($conversion);
        }

        return $urlGenerator;
    }
}





