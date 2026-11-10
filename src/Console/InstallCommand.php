<?php

namespace Javaabu\Cms\Console;

use Illuminate\Console\Command;
use Javaabu\Cms\Content\StarterContent;
use Javaabu\Cms\Upgrade\V1Tables;

/**
 * Sets up the CMS in an app: config, migrations, permissions and starter
 * content. Safe to run again.
 */
class InstallCommand extends Command
{
    protected $signature = 'cms:install
        {--no-migrate : Don\'t run the migrations}
        {--no-content : Don\'t create the starter content}
        {--force : Overwrite the published config, and run the migrations in production}';

    protected $description = 'Set up javaabu/cms: publish the config, migrate, sync permissions and create starter content';

    public function handle(): int
    {
        $this->components->info(__('Installing the CMS'));

        $this->publishConfig();

        if (! $this->option('no-migrate')) {
            $this->components->task(__('Running the migrations'), fn () => $this->callSilently('migrate', ['--force' => true]) === 0);
        }

        $this->components->task(__('Syncing permissions'), fn () => $this->callSilently('permissions:sync') === 0);

        if (! $this->option('no-content')) {
            $created = app(StarterContent::class)->seed();

            if ($created === []) {
                $this->components->info(__('The starter content is already there.'));
            } else {
                $this->components->bulletList($created);
            }
        }

        if (V1Tables::hasRenamed()) {
            $this->components->warn(__('Content from v1 was found. Run php artisan cms:import-v1 to bring it over.'));
        }

        $this->nextSteps();

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $exists = file_exists(config_path('cms.php'));

        if ($exists && ! $this->option('force')) {
            $this->components->twoColumnDetail('config/cms.php', __('already published'));

            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'cms-config', '--force' => (bool) $this->option('force')]);

        $this->components->twoColumnDetail('config/cms.php', __('published'));
    }

    protected function nextSteps(): void
    {
        $this->newLine();
        $this->line(__('Next, add the routes to routes/web.php (the public routes last):'));
        $this->newLine();
        $this->line("    Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);");
        $this->line('    Route::cms();');
        $this->newLine();
        $this->line(__('Give your admin role view_dashboard and the content permissions, then open /admin.'));
    }
}
