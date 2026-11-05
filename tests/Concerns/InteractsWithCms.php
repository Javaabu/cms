<?php

namespace Javaabu\Cms\Tests\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Javaabu\Cms\Tests\TestSupport\Models\Admin;
use Javaabu\Cms\Tests\TestSupport\Models\User;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;

trait InteractsWithCms
{
    /**
     * Options for Route::cmsAdmin() in defineRoutes()
     *
     * @return array<string, mixed>
     */
    protected function adminRouteOptions(): array
    {
        return ['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.'];
    }

    /**
     * Options for Route::cms() in defineRoutes()
     *
     * @return array<string, mixed>
     */
    protected function publicRouteOptions(): array
    {
        return [];
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function () {
            Route::cmsAdmin($this->adminRouteOptions());
            Route::cms($this->publicRouteOptions());
        });
    }

    protected function syncPermissions(): void
    {
        $this->artisan('permissions:sync')->assertSuccessful();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function admin(array $permissions = []): Admin
    {
        $admin = Admin::create([
            'name' => 'އަޙްމަދު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        if ($permissions !== []) {
            $admin->givePermissionTo($permissions);
        }

        return $admin;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function user(array $permissions = []): User
    {
        $user = User::create([
            'name' => 'ޢާއިޝަތު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createPost(string $type, array $attributes = []): Post
    {
        $model = PostTypes::postModel();

        return $model::create([
            'type' => $type,
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'status' => 'published',
            'published_at' => Carbon::now()->subDay(),
            ...$attributes,
        ]);
    }
}
