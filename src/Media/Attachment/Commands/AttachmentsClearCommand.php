<?php

namespace Javaabu\Cms\Media\Attachment\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Collection;
use Javaabu\Cms\Media\Attachment\AttachmentRepository;

class AttachmentsClearCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'attachments:clear {modelType?} {collectionName?}
    {-- force : Force the operation to run when in production}';

    protected $description = 'Delete all items in an attachment collection.';

    /** @var AttachmentRepository */
    protected $attachmentRepository;

    public function __construct(AttachmentRepository $attachmentRepository)
    {
        parent::__construct();
        $this->attachmentRepository = $attachmentRepository;
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->getAttachmentItems()->each->delete();

        $this->info('All done!');
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
}





