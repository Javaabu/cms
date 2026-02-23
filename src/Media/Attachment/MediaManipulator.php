<?php

namespace Javaabu\Cms\Media\Attachment;


use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\MediaLibrary\Support\ImageFactory;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\TemporaryDirectory;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImageGenerator;
use Javaabu\Cms\Media\Attachment\Jobs\PerformAttachmentConversions;
use Javaabu\Cms\Media\Attachment\Events\AttachmentConversionWillStart;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Javaabu\Cms\Media\Attachment\Conversion\AttachmentConversionCollection;
use Javaabu\Cms\Media\Attachment\Events\AttachmentConversionHasBeenCompleted;

class MediaManipulator extends FileManipulator
{
    /**
     * Create all derived files for the given media.
     *
     * @param Attachment $attachment
     * @param array $only
     * @param bool $onlyIfMissing
     */
    public function createDerivedAttachmentFiles(Attachment $attachment, array $only = [], $onlyIfMissing = false)
    {
        $profileCollection = AttachmentConversionCollection::createForAttachment($attachment);
        $media = $attachment->media;

        if (! empty($only)) {
            $profileCollection = $profileCollection->filter(function ($collection) use ($only) {
                return in_array($collection->getName(), $only);
            });
        }

        $this->performAttachmentConversions(
            $profileCollection->getNonQueuedConversions($attachment->collection_name),
            $attachment,
            $onlyIfMissing
        );

        $queuedConversions = $profileCollection->getQueuedConversions($attachment->collection_name);

        if ($queuedConversions->isNotEmpty()) {
            $this->dispatchQueuedAttachmentConversions($attachment, $queuedConversions);
        }
    }

    /**
     * Perform the given conversions for the given media.
     *
     * @param ConversionCollection $conversions
     * @param Attachment $attachment
     * @param bool $onlyIfMissing
     */
    public function performAttachmentConversions(ConversionCollection $conversions, Attachment $attachment, $onlyIfMissing = false)
    {
        $media = $attachment->media;

        if ($conversions->isEmpty()) {
            return;
        }

        $imageGenerator = ImageGeneratorFactory::forMedia($media);

        if (! $imageGenerator) {
            return;
        }

        $temporaryDirectory = TemporaryDirectory::create();

        $copiedOriginalFile = $this->filesystem()->copyFromMediaLibrary(
            $media,
            $temporaryDirectory->path(Str::random(16) . '.' . $media->extension)
        );

        $conversions
            ->reject(function (Conversion $conversion) use ($onlyIfMissing, $attachment) {
                $media = $attachment->media;

                $relativePath = $attachment->getPath($conversion->getName());

                $rootPath = config('filesystems.disks.' . $media->disk . '.root');

                if ($rootPath) {
                    $relativePath = str_replace($rootPath, '', $relativePath);
                }

                return $onlyIfMissing && Storage::disk($media->disk)->exists($relativePath);
            })
            ->each(function (Conversion $conversion) use ($attachment, $media, $imageGenerator, $copiedOriginalFile) {
                event(new AttachmentConversionWillStart($attachment, $conversion, $copiedOriginalFile));

                $copiedOriginalFile = $imageGenerator->convert($copiedOriginalFile, $conversion);

                $manipulationResult = $this->performManipulations($media, $conversion, $copiedOriginalFile);

                $newFileName = $conversion->getConversionFile($media);

                $renamedFile = $this->renameInLocalDirectory($manipulationResult, $newFileName);

                if ($conversion->shouldGenerateResponsiveImages()) {
                    /** @var ResponsiveImageGenerator $responsiveImageGenerator */
                    $responsiveImageGenerator = app(ResponsiveImageGenerator::class);

                    $responsiveImageGenerator->generateResponsiveImagesForConversion(
                        $media,
                        $conversion,
                        $renamedFile
                    );
                }

                $this->filesystem()->copyToMediaLibrary($renamedFile, $media, 'conversions');

                $media->markAsConversionGenerated($conversion->getName(), true);

                event(new AttachmentConversionHasBeenCompleted($attachment, $conversion));
            });

        $temporaryDirectory->delete();
    }

    /**
     * Dispatch the conversions
     *
     * @param Attachment $attachment
     * @param ConversionCollection $queuedConversions
     */
    protected function dispatchQueuedAttachmentConversions(Attachment $attachment, ConversionCollection $queuedConversions)
    {
        $performConversionsJobClass = PerformAttachmentConversions::class;

        $job = new $performConversionsJobClass($queuedConversions, $attachment);

        if ($customQueue = config('media-library.queue_name')) {
            $job->onQueue($customQueue);
        }

        app(Dispatcher::class)->dispatch($job);
    }

    protected function filesystem(): Filesystem
    {
        return app(Filesystem::class);
    }

    public function performManipulations(Media $media, Conversion $conversion, string $imageFile): string
    {
        if ($conversion->getManipulations()->isEmpty()) {
            return $imageFile;
        }

        $conversionTempFile = $this->getConversionTempFileName($media, $conversion, $imageFile);

        File::copy($imageFile, $conversionTempFile);

        $supportedFormats = ['jpg', 'pjpg', 'png', 'gif'];
        if ($conversion->shouldKeepOriginalImageFormat() && in_array($media->extension, $supportedFormats)) {
            $conversion->format($media->extension);
        }

        $image = ImageFactory::load($conversionTempFile);

        foreach ($conversion->getManipulations() as $manipulation => $args) {
            $image->{$manipulation}(...$args);
        }

        $image->save();

        return $conversionTempFile;
    }

    protected function getConversionTempFileName(Media $media, Conversion $conversion, string $imageFile): string
    {
        $directory = pathinfo($imageFile, PATHINFO_DIRNAME);

        $fileName = Str::random(16)."{$conversion->getName()}.{$media->extension}";

        return "{$directory}/{$fileName}";
    }

    protected function renameInLocalDirectory(
        string $fileNameWithDirectory,
        string $newFileNameWithoutDirectory
    ): string {
        $targetFile = pathinfo($fileNameWithDirectory, PATHINFO_DIRNAME).'/'.$newFileNameWithoutDirectory;

        rename($fileNameWithDirectory, $targetFile);

        return $targetFile;
    }
}





