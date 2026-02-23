<?php

namespace Javaabu\Cms\Media\Attachment;

use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\ServiceProvider;
use Javaabu\Cms\Media\Attachment\Commands\AttachmentsCleanCommand;
use Javaabu\Cms\Media\Attachment\Commands\AttachmentsClearCommand;
use Javaabu\Cms\Media\Attachment\Commands\AttachmentsRegenerateCommand;

class AttachmentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $attachmentClass = Attachment::class;

        $attachmentClass::observe(new AttachmentObserver());
    }

    public function register()
    {
        $this->app->singleton(AttachmentRepository::class, function () {
            $attachmentClass = Attachment::class;

            return new AttachmentRepository(new $attachmentClass);
        });

        $this->app->bind('command.attachments:regenerate', AttachmentsRegenerateCommand::class);
        $this->app->bind('command.attachments:clear', AttachmentsClearCommand::class);
        $this->app->bind('command.attachments:clean', AttachmentsCleanCommand::class);

        $this->commands([
            'command.attachments:regenerate',
            'command.attachments:clear',
            'command.attachments:clean',
        ]);
    }
}





