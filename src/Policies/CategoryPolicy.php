<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Auth\User;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;

class CategoryPolicy
{

    /**
     * Determine whether the user can see view any categories
     */
    public function viewAny(User $user, CategoryType $category_type)
    {
        return $user->can('viewAny', $category_type);
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category)
    {
        return $user->can('view', $category->type);
    }

    /**
     * Determine whether the user can create category.
     */
    public function create(User $user, CategoryType $category_type)
    {
        return $user->can('create', $category_type);
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category)
    {
        return $user->can('delete', $category->type);
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category)
    {
        return $user->can('update', $category->type);
    }
}
