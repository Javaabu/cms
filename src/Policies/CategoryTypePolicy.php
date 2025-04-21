<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Auth\User;
use Javaabu\Cms\Models\CategoryType;

class CategoryTypePolicy
{
    /**
     * Determine whether the user can see view any category types
     */
    public function viewAny(User $user, CategoryType $category_type): bool
    {
        return $user->can('view_' . $category_type->permission_slug);
    }

    /**
     * Determine whether the user can view the category type.
     */
    public function view(User $user, CategoryType $category_type): bool
    {
        return $user->can('view_' . $category_type->permission_slug);
    }

    /**
     * Determine whether the user can create category type.
     */
    public function create(User $user, CategoryType $category_type): bool
    {
        return $user->can('edit_' . $category_type->permission_slug);
    }

    /**
     * Determine whether the user can delete the category type.
     */
    public function delete(User $user, CategoryType $category_type): bool
    {
        return $user->can('delete_' . $category_type->permission_slug);
    }

    /**
     * Determine whether the user can update the category type.
     */
    public function update(User $user, CategoryType $category_type): bool
    {
        return $user->can('edit_' . $category_type->permission_slug);
    }
}
