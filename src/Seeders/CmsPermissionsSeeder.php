<?php

namespace Javaabu\Cms\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Cms\Models\PostType;
use Spatie\Permission\Models\Permission;

class CmsPermissionsSeeder extends Seeder
{
    protected array $all_data = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        static::seedPermissions();
    }

    /**
     * Seed CMS permissions - can be called from application seeder
     *
     * @return void
     */
    public static function seedPermissions(): void
    {
        $permissions = (new CmsPermissionsSeeder)->getPermissions();

        foreach ($permissions as $model => $permission) {
            static::createOrUpdatePermission($model, $permission);
        }

        // Add media permissions and assign to default roles
        static::createOrUpdatePermission('media', static::getMediaPermissions());
        static::assignMediaToDefaultRoles();
    }

    /**
     * Get all CMS permissions
     *
     * @return array
     */
    public function getPermissions(): array
    {
        $this->loadPostTypePermissions();
        $this->loadCategoryTypePermissions();

        return $this->all_data;
    }

    /**
     * Load up permissions for all the Post Types
     *
     * @return void
     */
    private function loadPostTypePermissions()
    {
        $all_post_types = PostType::all();

        foreach ($all_post_types as $post_type) {
            $permissions = $this->constructPostTypePermissions($post_type);
            $existing = $this->all_data[$post_type->permission_slug] ?? [];
            $this->all_data[$post_type->permission_slug] = array_merge($existing, $permissions);
        }
    }

    /**
     * Permission template for the post types
     *
     * @param PostType $post_type
     * @return string[]
     */
    protected function constructPostTypePermissions(PostType $post_type): array
    {
        $slug = $post_type->permission_slug;
        $title = Str::lower($post_type->name_en);

        return [
            'edit_' . $slug                => 'Edit own ' . $title,
            'edit_others_' . $slug         => 'Edit all ' . $title,
            'delete_' . $slug              => 'Delete own ' . $title,
            'delete_others_' . $slug       => 'Delete all ' . $title,
            'view_' . $slug                => 'View own ' . $title,
            'view_others_' . $slug         => 'View all ' . $title,
            'force_delete_' . $slug        => 'Force delete own ' . $title,
            'force_delete_others_' . $slug => 'Force delete all ' . $title,
            'publish_' . $slug             => 'Publish own ' . $title,
            'publish_others_' . $slug      => 'Publish all ' . $title,
            'import_' . $slug              => 'Import ' . $title,
        ];
    }

    /**
     * Load up permissions for all the Category Types
     *
     * @return void
     */
    protected function loadCategoryTypePermissions()
    {
        $all_category_types = CategoryType::all();

        foreach ($all_category_types as $category_type) {
            $permissions = $this->constructCategoryTypePermissions($category_type);
            $existing = $this->all_data[$category_type->permission_slug] ?? [];
            $this->all_data[$category_type->permission_slug] = array_merge($existing, $permissions);
        }
    }

    /**
     * Permission template for the category types
     *
     * @param CategoryType $category_type
     * @return string[]
     */
    protected function constructCategoryTypePermissions(CategoryType $category_type): array
    {
        $slug = $category_type->permission_slug;
        $title = Str::lower($category_type->name_en);

        return [
            'edit_' . $slug   => 'Edit ' . $title,
            'delete_' . $slug => 'Delete ' . $title,
            'view_' . $slug   => 'View ' . $title,
            'import_' . $slug => 'Import ' . $title,
        ];
    }

    /**
     * Get media permissions
     *
     * @return array
     */
    protected static function getMediaPermissions(): array
    {
        return [
            'view_media'              => 'View own media',
            'view_other_users_media'  => 'View all media',
            'edit_media'              => 'Edit own media',
            'edit_other_users_media'  => 'Edit all media',
            'delete_media'            => 'Delete own media',
            'delete_other_users_media'=> 'Delete all media',
        ];
    }

    /**
     * Create or update a permission
     *
     * @param array $permissionData
     * @return void
     */
    protected static function createOrUpdatePermission(string $model, array $permissions): void
    {
        // Try to use Javaabu Permissions package if available
        if (class_exists('\Javaabu\Permissions\Models\Permission')) {
            $permissionClass = '\Javaabu\Permissions\Models\Permission';
        } elseif (class_exists('\Spatie\Permission\Models\Permission')) {
            $permissionClass = '\Spatie\Permission\Models\Permission';
        } else {
            // No permission system available
            return;
        }

        foreach ($permissions as $name => $desc) {
            $permission = $permissionClass::firstOrCreate(['name' => $name, 'guard_name' => 'web_admin']);
            $permission->update(['description' => $desc, 'model' => $model]);
            $permission->save();
        }
    }


    /**
     * Assign media permissions to default roles
     *
     * @return void
     */
    protected static function assignMediaToDefaultRoles(): void
    {
        if (class_exists('\Javaabu\Permissions\Models\Role')) {
            $roleClass = '\Javaabu\Permissions\Models\Role';
        } elseif (class_exists('\Spatie\Permission\Models\Role')) {
            $roleClass = '\Spatie\Permission\Models\Role';
        } else {
            return;
        }

        $defaultRoles = config('cms.default_roles', ['admin', 'editor']);
        $permissionNames = array_keys(static::getMediaPermissions());

        foreach ($defaultRoles as $roleName) {
            $role = $roleClass::where('name', $roleName)->where('guard_name', 'web_admin')->first();

            if ($role) {
                $role->givePermissionTo($permissionNames);
            }
        }
    }
}





