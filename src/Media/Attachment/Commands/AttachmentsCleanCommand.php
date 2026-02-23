<?php

namespace Javaabu\Cms\Media\Attachment\Commands;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Javaabu\Cms\Media\Attachment\MediaManipulator;
use Javaabu\Cms\Media\Attachment\AttachmentRepository;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Spatie\MediaLibrary\ResponsiveImages\RegisteredResponsiveImages;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Javaabu\Cms\Media\Attachment\Conversion\AttachmentConversionCollection;

class AttachmentsCleanCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'attachments:clean {modelType?} {collectionName?} {disk?}
    {--dry-run : List files that will be removed without removing them},
    {--force : Force the operation to run when in production},
    {--rate-limit= : Limit the number of request per second }';

    protected $description = 'Clean deprecated conversions and files without related model.';

    /** @var AttachmentRepository */
    protected $attachmentRepository;

    /** @var MediaManipulator */
    protected $mediaManipulator;

    /** @var Factory */
    protected $fileSystem;

    /** @var DefaultPathGenerator */
    protected $DefaultPathGenerator;

    /** @var bool */
    protected $isDryRun = false;

    /** @var int */
    protected $rateLimit = 0;

    /**
     * @param AttachmentRepository $attachmentRepository
     * @param MediaManipulator $mediaManipulator
     * @param Factory $fileSystem
     * @param DefaultPathGenerator $DefaultPathGenerator
     */
    public function __construct(
        AttachmentRepository $attachmentRepository,
        MediaManipulator     $mediaManipulator,
        Factory              $fileSystem,
        DefaultPathGenerator $DefaultPathGenerator
    )
    {
        parent::__construct();

        $this->attachmentRepository = $attachmentRepository;
        $this->mediaManipulator = $mediaManipulator;
        $this->fileSystem = $fileSystem;
        $this->DefaultPathGenerator = $DefaultPathGenerator;
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->isDryRun = $this->option('dry-run');
        $this->rateLimit = (int)$this->option('rate-limit');

        $this->deleteFilesGeneratedForDeprecatedConversions();

        $this->deleteOrphanedDirectories();

        $this->info('All done!');
    }

    protected function deleteFilesGeneratedForDeprecatedConversions()
    {
        $this->getAttachmentItems()->each(function (Attachment $attachment) {
            $media = $attachment->media;

            $this->deleteConversionFilesForDeprecatedConversions($attachment);

            if ($media->responsive_images) {
                $this->deleteResponsiveImagesForDeprecatedConversions($attachment);
            }

            if ($this->rateLimit) {
                usleep((1 / $this->rateLimit) * 1000000 * 2);
            }
        });
    }

    public function getAttachmentItems(): Collection
    {
        $modelType = $this->argument('modelType');
        $collectionName = $this->argument('collectionName');

        if (! is_null($modelType) && ! is_null($collectionName)) {
            return $this->attachmentRepository->getByModelTypeAndCollectionName(
                $modelType,
                $collectionName
            );
        }

        if (! is_null($modelType)) {
            return $this->attachmentRepository->getByModelType($modelType);
        }

        if (! is_null($collectionName)) {
            return $this->attachmentRepository->getByCollectionName($collectionName);
        }

        return $this->attachmentRepository->all();
    }

    protected function deleteConversionFilesForDeprecatedConversions(Attachment $attachment)
    {
        $media = $attachment->media;
        $conversionFilePaths = AttachmentConversionCollection::createForAttachment($attachment)->getConversionsFiles($attachment->collection_name);

        $conversionPath = $this->DefaultPathGenerator->getPathForConversions($media);
        $currentFilePaths = $this->fileSystem->disk($media->disk)->files($conversionPath);

        collect($currentFilePaths)
            ->reject(function (string $currentFilePath) use ($conversionFilePaths) {
                return $conversionFilePaths->contains(basename($currentFilePath));
            })
            ->each(function (string $currentFilePath) use ($media) {
                if (! $this->isDryRun) {
                    $this->fileSystem->disk($media->disk)->delete($currentFilePath);

                    $this->markConversionAsRemoved($media, $currentFilePath);
                }

                $this->info("Deprecated conversion file `{$currentFilePath}` " . ($this->isDryRun ? 'found' : 'has been removed'));
            });
    }

    protected function markConversionAsRemoved(Media $media, string $conversionPath)
    {
        $conversionFile = pathinfo($conversionPath, PATHINFO_FILENAME);

        $generatedConversionName = null;

        $media->getGeneratedConversions()
              ->filter(function (bool $isGenerated, string $generatedConversionName) use ($conversionFile) {
                  return str_contains($conversionFile, $generatedConversionName);
              })
              ->each(function (bool $isGenerated, string $generatedConversionName) use ($media) {
                  $media->markAsConversionGenerated($generatedConversionName, false);
              });

        $media->save();
    }

    protected function deleteResponsiveImagesForDeprecatedConversions(Attachment $attachment)
    {
        $media = $attachment->media;

        $conversionNames = AttachmentConversionCollection::createForAttachment($attachment)
                                                         ->map(function (Conversion $conversion) {
                                                             return $conversion->getName();
                                                         })
                                                         ->push('medialibrary_original');

        $responsiveImagesGeneratedFor = array_keys($media->responsive_images);

        collect($responsiveImagesGeneratedFor)
            ->map(function (string $generatedFor) use ($media) {
                return $media->responsiveImages($generatedFor);
            })
            ->reject(function (RegisteredResponsiveImages $responsiveImages) use ($conversionNames) {
                return $conversionNames->contains($responsiveImages->generatedFor);
            })
            ->each(function (RegisteredResponsiveImages $responsiveImages) {
                if (! $this->isDryRun) {
                    $responsiveImages->delete();
                }
            });
    }

    protected function deleteOrphanedDirectories()
    {
        $diskName = $this->argument('disk') ?: config('media-library.disk_name');

        if (is_null(config("filesystems.disks.{$diskName}"))) {
            throw FileCannotBeAdded::diskDoesNotExist($diskName);
        }

        $mediaIds = collect($this->attachmentRepository->all()->pluck('media_id')->toArray());

        collect($this->fileSystem->disk($diskName)->directories())
            ->filter(function (string $directory) {
                return is_numeric($directory);
            })
            ->reject(function (string $directory) use ($mediaIds) {
                return $mediaIds->contains((int)$directory);
            })->each(function (string $directory) use ($diskName) {
                if (! $this->isDryRun) {
                    $this->fileSystem->disk($diskName)->deleteDirectory($directory);
                }

                if ($this->rateLimit) {
                    usleep((1 / $this->rateLimit) * 1000000);
                }

                $this->info("Orphaned media directory `{$directory}` " . ($this->isDryRun ? 'found' : 'has been removed'));
            });
    }
}





