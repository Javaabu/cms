<?php

namespace Javaabu\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Javaabu\Cms\Seeders\CmsPermissionsSeeder;
use Javaabu\Cms\Seeders\DefaultPostsAndCategoriesSeeder;

class SetupCmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:setup
                            {--skip-migrations : Skip running migrations}
                            {--skip-permissions : Skip seeding permissions}
                            {--with-defaults : Install default post types and categories}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the Javaabu CMS package - publishes config, migrations, and seeds data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Setting up Javaabu CMS...');
        $this->newLine();

        // Publish config
        $this->comment('📦 Publishing CMS configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'cms-config',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Config published successfully');
        $this->newLine();

        // Setup translations
        $this->setupTranslations();

        // Update package.json and vite.config.js
        $this->setupFrontend();

        // Publish migrations
        if (!$this->option('skip-migrations')) {
            $this->comment('📦 Publishing CMS migrations...');
            $this->call('vendor:publish', [
                '--tag' => 'cms-migrations',
                '--force' => $this->option('force'),
            ]);
            $this->info('✓ Migrations published successfully');
            $this->newLine();

            // Ask to run migrations
            if ($this->confirm('Do you want to run the migrations now?', true)) {
                $this->comment('🔄 Running migrations...');
                $this->call('migrate');
                $this->info('✓ Migrations completed');
                $this->newLine();
            }
        }

        // Seed permissions and defaults
        if (!$this->option('skip-permissions')) {
            $seedDefaults = $this->option('with-defaults') || $this->confirm('Do you want to install default post types and categories?', false);

            if ($seedDefaults) {
                $this->comment('🌱 Seeding default post types and categories...');
                try {
                    DefaultPostsAndCategoriesSeeder::seedDefaults();
                    $this->info('✓ Default post types and categories seeded successfully');
                    $this->newLine();
                } catch (\Exception $e) {
                    $this->error('✗ Error seeding defaults: ' . $e->getMessage());
                    $this->warn('You may need to seed defaults manually.');
                    $this->newLine();
                }
            }

            if ($this->confirm('Do you want to seed CMS permissions?', true)) {
                $this->comment('🌱 Seeding CMS permissions...');
                try {
                    CmsPermissionsSeeder::seedPermissions();
                    $this->info('✓ Permissions seeded successfully');
                } catch (\Exception $e) {
                    $this->error('✗ Error seeding permissions: ' . $e->getMessage());
                    $this->warn('You may need to seed permissions manually.');
                }
                $this->newLine();
            }
        }

        $this->comment('📦 Publishing Media assets...');
        $this->call('vendor:publish', [
            '--tag' => 'cms-assets',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Media assets published successfully');
        $this->newLine();

        // Display next steps
        $this->displayNextSteps();

        return Command::SUCCESS;
    }

    /**
     * Handle translation setup
     */
    protected function setupTranslations()
    {
        if ($this->confirm('Do you want to enable multi-language translations?', false)) {
            $this->comment('🌐 Enabling translations...');

            $configPath = config_path('cms.php');

            if (File::exists($configPath)) {
                $content = File::get($configPath);

                // Update should_translate
                $content = preg_replace(
                    "/(['\"]should_translate['\"]\s*=>\s*)false/",
                    "$1true",
                    $content
                );

                // Update models
                $replacements = [
                    'post' => '\Javaabu\Cms\Models\TranslatablePost::class',
                    'category' => '\Javaabu\Cms\Models\TranslatableCategory::class',
                    'tag' => '\Javaabu\Cms\Models\TranslatableTag::class',
                ];

                foreach ($replacements as $key => $newClass) {
                    $content = preg_replace(
                        "/(['\"]" . $key . "['\"]\s*=>\s*).*?,/",
                        "$1$newClass,",
                        $content
                    );
                }

                // Update controllers
                $controllerReplacements = [
                    'posts' => '\Javaabu\Cms\Translatable\Http\Controllers\Admin\PostsController::class',
                    'categories' => '\Javaabu\Cms\Translatable\Http\Controllers\Admin\CategoriesController::class',
                ];

                foreach ($controllerReplacements as $key => $newClass) {
                    $content = preg_replace(
                        "/(['\"]" . $key . "['\"]\s*=>\s*).*?,/",
                        "$1$newClass,",
                        $content
                    );
                }

                // Update web controllers
                $content = preg_replace(
                    "/(['\"]web['\"]\s*=>\s*\[\s*['\"]controllers['\"]\s*=>\s*\[\s*['\"]posts['\"]\s*=>\s*).*?,/",
                    "$1\Javaabu\Cms\Translatable\Http\Controllers\PostsController::class,",
                    $content
                );

                File::put($configPath, $content);
                $this->info('✓ Translations enabled and models updated in config/cms.php');
            } else {
                $this->warn('✗ could not find config/cms.php to update translation settings.');
            }

            $this->newLine();
        }
    }

    /**
     * Handle package.json and vite.config.js updates
     */
    protected function setupFrontend()
    {
        $this->comment('🎨 Setting up frontend assets...');

        $packagePath = __DIR__ . '/../../../';

        $projectPath = base_path();

        // --- Update package.json ---
        if (File::exists($packagePath . 'package.json')) {
            $this->updatePackageJson($packagePath, $projectPath);
        }

        $this->info('✓ Frontend configuration updated');
        $this->newLine();
    }

    /**
     * Merges package dependencies into the main package.json
     */
    protected function updatePackageJson($packagePath, $projectPath)
    {
        $pkgJson = json_decode(File::get($packagePath . 'package.json'), true);
        $mainJson = json_decode(File::get($projectPath . '/package.json'), true);

        $dependencies = $pkgJson['dependencies'] ?? [];
        $devDependencies = $pkgJson['devDependencies'] ?? [];

        $mainJson['dependencies'] = array_merge($mainJson['dependencies'] ?? [], $dependencies);
        $mainJson['devDependencies'] = array_merge($mainJson['devDependencies'] ?? [], $devDependencies);

        File::put(
            $projectPath . '/package.json',
            json_encode($mainJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->line('   - Dependencies added to package.json');
    }

    /**
     * Display next steps to the user
     *
     * @return void
     */
    protected function displayNextSteps(): void
    {
        $this->info('✅ Javaabu CMS setup completed!');
        $this->newLine();

        $this->comment('📝 Next Steps:');
        $this->line('1. Register CMS routes in your routes/web.php:');
        $this->line('   - Admin: Javaabu\Cms\Support\Routes::admin();');
        $this->line('   - Web: Javaabu\Cms\Support\Routes::web();');
        $this->line('2. Start creating content in the admin panel!');
        $this->newLine();

        $this->comment('💡 Tip: You can customize or add more post types in config/cms.php');
        $this->newLine();

        $this->comment('📚 Documentation: Check docs/ folder for detailed guides');
        $this->newLine();
    }
}





