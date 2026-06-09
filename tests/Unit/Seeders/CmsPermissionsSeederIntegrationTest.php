<?php

namespace Spatie\Permission\Models;

class Permission
{
    public static array $records = [];

    public array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public static function firstOrCreate(array $attributes): self
    {
        $key = $attributes['name'] . '|' . $attributes['guard_name'];

        if (! isset(static::$records[$key])) {
            static::$records[$key] = new self($attributes);
        }

        return static::$records[$key];
    }

    public function update(array $attributes): void
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    public function save(): void
    {
    }
}

class Role
{
    public static array $roles = [];

    public static function where(string $field, mixed $value): FakeRoleQuery
    {
        return new FakeRoleQuery($field, $value);
    }

    public function __construct(
        public string $name,
        public string $guard_name,
        public array $permissions = []
    ) {
    }

    public function givePermissionTo(array $permissionNames): void
    {
        $this->permissions = array_values(array_unique(array_merge($this->permissions, $permissionNames)));
    }
}

class FakeRoleQuery
{
    private array $criteria = [];

    public function __construct(string $field, mixed $value)
    {
        $this->criteria[$field] = $value;
    }

    public function where(string $field, mixed $value): self
    {
        $this->criteria[$field] = $value;

        return $this;
    }

    public function first(): ?Role
    {
        foreach (Role::$roles as $role) {
            if ($role->name === ($this->criteria['name'] ?? null)
                && $role->guard_name === ($this->criteria['guard_name'] ?? null)) {
                return $role;
            }
        }

        return null;
    }
}

namespace Javaabu\Cms\Tests\Unit\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Seeders\CmsPermissionsSeeder;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CmsPermissionsSeederIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Permission::$records = [];
        Role::$roles = [
            new Role('admin', 'web_admin'),
            new Role('editor', 'web_admin'),
        ];
    }

    #[Test]
    public function it_seeds_permissions_and_assigns_media_permissions_to_existing_default_roles(): void
    {
        config()->set('cms.default_roles', ['admin', 'editor', 'missing-role']);

        $postType = new PostType([
            'name' => 'Press Releases',
            'singular_name' => 'Press Release',
            'slug' => 'press-releases',
            'icon' => 'ri-newspaper-line',
        ]);
        $postType->lang = 'en';
        $postType->save();

        $categoryType = new CategoryType([
            'name' => 'News Categories',
            'singular_name' => 'News Category',
            'slug' => 'news-categories',
        ]);
        $categoryType->lang = 'en';
        $categoryType->save();

        (new CmsPermissionsSeeder())->run();

        $this->assertArrayHasKey('edit_press_releases|web_admin', Permission::$records);
        $this->assertArrayHasKey('delete_news_categories|web_admin', Permission::$records);
        $this->assertArrayHasKey('view_media|web_admin', Permission::$records);
        $this->assertSame('press_releases', Permission::$records['edit_press_releases|web_admin']->attributes['model']);
        $this->assertSame('media', Permission::$records['delete_other_users_media|web_admin']->attributes['model']);
        $this->assertContains('view_media', Role::$roles[0]->permissions);
        $this->assertContains('delete_other_users_media', Role::$roles[1]->permissions);
    }
}
