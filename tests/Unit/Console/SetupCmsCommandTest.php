<?php

namespace Javaabu\Cms\Tests\Unit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Javaabu\Cms\Console\Commands\SetupCmsCommand;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SetupCmsCommandTest extends TestCase
{
    #[Test]
    public function handle_runs_publish_pipeline_with_skip_options_enabled(): void
    {
        $command = \Mockery::mock(SetupCmsCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('info')->atLeast()->once();
        $command->shouldReceive('comment')->atLeast()->once();
        $command->shouldReceive('newLine')->atLeast()->once();

        $command->shouldReceive('setupTranslations')->once();
        $command->shouldReceive('setupFrontend')->once();
        $command->shouldReceive('displayNextSteps')->once();

        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldReceive('option')->with('skip-migrations')->andReturn(true);
        $command->shouldReceive('option')->with('skip-permissions')->andReturn(true);

        $command->shouldReceive('call')->once()->with('vendor:publish', \Mockery::on(function (array $args) {
            return $args['--tag'] === 'cms-config';
        }));
        $command->shouldReceive('call')->once()->with('vendor:publish', \Mockery::on(function (array $args) {
            return $args['--tag'] === 'cms-assets';
        }));

        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    #[Test]
    public function setup_translations_updates_config_when_enabled(): void
    {
        $configPath = config_path('cms.php');
        $command = \Mockery::mock(SetupCmsCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('confirm')->once()->andReturn(true);
        $command->shouldReceive('comment')->atLeast()->once();
        $command->shouldReceive('info')->once();
        $command->shouldReceive('newLine')->once();

        File::shouldReceive('exists')->once()->with($configPath)->andReturn(true);
        File::shouldReceive('get')->once()->with($configPath)->andReturn(<<<'PHP'
<?php
return [
    'should_translate' => false,
    'models' => [
        'post' => \Javaabu\Cms\Models\Post::class,
        'category' => \Javaabu\Cms\Models\Category::class,
        'tag' => \Javaabu\Cms\Models\Tag::class,
    ],
    'controllers' => [
        'posts' => \Javaabu\Cms\Http\Controllers\Admin\PostsController::class,
        'categories' => \Javaabu\Cms\Http\Controllers\Admin\CategoriesController::class,
    ],
    'web' => [
        'controllers' => [
            'posts' => \Javaabu\Cms\Http\Controllers\PostsController::class,
        ],
    ],
];
PHP);
        File::shouldReceive('put')->once()->with($configPath, \Mockery::on(function (string $updated): bool {
            return str_contains($updated, "'should_translate' => true")
                && str_contains($updated, 'TranslatablePost::class')
                && str_contains($updated, 'TranslatableCategory::class')
                && str_contains($updated, 'TranslatableTag::class')
                && str_contains($updated, 'Translatable\\Http\\Controllers\\Admin\\PostsController::class')
                && str_contains($updated, 'Translatable\\Http\\Controllers\\Admin\\CategoriesController::class')
                && str_contains($updated, 'Translatable\\Http\\Controllers\\PostsController::class');
        }));

        $method = new \ReflectionMethod($command, 'setupTranslations');
        $method->setAccessible(true);
        $method->invoke($command);
    }

    #[Test]
    public function update_package_json_merges_dependencies_and_dev_dependencies(): void
    {
        $command = \Mockery::mock(SetupCmsCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('line')->once();

        $packagePath = '/virtual/package/';
        $projectPath = '/virtual/project';

        File::shouldReceive('get')->once()->with($packagePath . 'package.json')->andReturn(json_encode([
            'dependencies' => ['axios' => '^1.8.0'],
            'devDependencies' => ['vite' => '^6.0.0'],
        ]));
        File::shouldReceive('get')->once()->with($projectPath . '/package.json')->andReturn(json_encode([
            'dependencies' => ['alpinejs' => '^3.14.0'],
            'devDependencies' => ['tailwindcss' => '^4.0.0'],
        ]));
        File::shouldReceive('put')->once()->with($projectPath . '/package.json', \Mockery::on(function (string $json): bool {
            $decoded = json_decode($json, true);
            return isset($decoded['dependencies']['axios'])
                && isset($decoded['dependencies']['alpinejs'])
                && isset($decoded['devDependencies']['vite'])
                && isset($decoded['devDependencies']['tailwindcss']);
        }));

        $method = new \ReflectionMethod($command, 'updatePackageJson');
        $method->setAccessible(true);
        $method->invoke($command, $packagePath, $projectPath);
    }
}

