<?php

namespace Javaabu\Cms\Console;

use Illuminate\Console\Command;

class ImportV1Command extends Command
{
    protected $signature = 'cms:import-v1';

    protected $description = 'Import the content of javaabu/cms v1';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
