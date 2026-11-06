<?php

namespace Javaabu\Cms\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Javaabu\Cms\Tests\TestCase;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use PHPUnit\Framework\Attributes\Test;

class AssetsTest extends TestCase
{
    #[Test]
    public function blade_apps_need_no_npm_packages_for_the_cms(): void
    {
        $package = $this->app->make(Manifest::class)->packages()['cms'];

        $this->assertSame([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('@inertiajs/react', $package->npmPeerDependencies(NpmScopes::PAGES));
    }

    #[Test]
    public function manifest_check_only_asks_inertia_apps_for_the_cms_react_packages(): void
    {
        $assets = $this->cmsRows(['assets']);
        $pages = $this->cmsRows(['pages']);

        $this->assertNotContains('@javaabu/forms', array_column($assets, 'name'));
        $this->assertNotContains('react', array_column($assets, 'name'));
        $this->assertContains('@javaabu/forms', array_column($pages, 'name'));
        $this->assertContains('react', array_column($pages, 'name'));
    }

    /**
     * manifest:check's rows that the cms package needs, for these scopes
     *
     * @param  array<int, string>  $scopes
     * @return array<int, array<string, mixed>>
     */
    protected function cmsRows(array $scopes): array
    {
        Artisan::call('manifest:check', ['--scope' => $scopes, '--json' => true]);

        $rows = json_decode(Artisan::output(), true) ?? [];

        return array_values(array_filter($rows, fn (array $row) => in_array('cms', $row['needed_by'], true)));
    }
}
