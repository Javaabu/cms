<?php

namespace Javaabu\Cms\Media\Attachment\Commands;

use Exception;
use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Console\ConfirmableTrait;
use Javaabu\Cms\Media\Attachment\MediaManipulator;
use Javaabu\Cms\Media\Attachment\AttachmentRepository;

class AttachmentsRegenerateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'attachments:regenerate {modelType?} {--ids=*}
    {--only=* : Regenerate specific conversions}
    {--only-missing : Regenerate only missing conversions}
    {--force : Force the operation to run when in production}';

    protected $description = 'Regenerate the derived images of media';

    /** @var AttachmentRepository */
    protected $attachmentRepository;

    /** @var MediaManipulator */
    protected $mediaManipulator;

    /** @var array */
    protected $erroredMediaIds = [];

    public function __construct(AttachmentRepository $attachmentRepository, MediaManipulator $mediaManipulator)
    {
        parent::__construct();

        $this->attachmentRepository = $attachmentRepository;
        $this->mediaManipulator = $mediaManipulator;
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $attachments = $this->getAttachmentsToBeRegenerated();

        $progressBar = $this->output->createProgressBar($attachments->count());

        $this->errorMessages = [];

        $attachments->each(function (Attachment $attachment) use ($progressBar) {
            try {
                $this->mediaManipulator->createDerivedAttachmentFiles(
                    $attachment,
                    Arr::wrap($this->option('only')),
                    $this->option('only-missing')
                );
            } catch (Exception $exception) {
                $this->errorMessages[$attachment->id] = $exception->getMessage();
            }

            $progressBar->advance();
        });

        $progressBar->finish();

        if (count($this->errorMessages)) {
            $this->warn('All done, but with some error messages:');

            foreach ($this->errorMessages as $attachmentId => $message) {
                $this->warn("Attachment id {$attachmentId}: `{$message}`");
            }
        }

        $this->info('All done!');
    }

    public function getAttachmentsToBeRegenerated(): Collection
    {
        $modelType = $this->argument('modelType') ?? '';
        $attachmentIds = $this->getAttachmentIds();

        if ($modelType === '' && count($attachmentIds) === 0) {
            return $this->attachmentRepository->all();
        }

        if (! count($attachmentIds)) {
            return $this->attachmentRepository->getByModelType($modelType);
        }

        return $this->attachmentRepository->getByIds($attachmentIds);
    }

    protected function getAttachmentIds(): array
    {
        $attachmentIds = $this->option('ids');

        if (! is_array($attachmentIds)) {
            $attachmentIds = explode(',', $attachmentIds);
        }

        if (count($attachmentIds) === 1 && Str::contains($attachmentIds[0], ',')) {
            $attachmentIds = explode(',', $attachmentIds[0]);
        }

        return $attachmentIds;
    }
}





