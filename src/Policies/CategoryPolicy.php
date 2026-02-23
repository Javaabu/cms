<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Illuminate\Foundation\Auth\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can see view any categories
     */
    public function viewAny(User $user, CategoryType $category_type): bool
    {
        return $user->can('viewAny', $category_type);
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->can('view', $category->type);
    }

    /**
     * Determine whether the user can create category.
     */
    public function create(User $user, CategoryType $category_type): bool
    {
        return $user->can('create', $category_type);
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can('update', $category->type);
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can('delete', $category->type);
    }

    /**
     * Determine whether the user can view logs of the category.
     */
    public function viewLogs(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
