<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Illuminate\Foundation\Auth\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, PostType $post_type): bool
    {
        return $user->can('viewAny', $post_type);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->can('viewAny', $post->postType);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, PostType $post_type): bool
    {
        return $user->can('create', $post_type);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->can('update', $post->postType);
    }

    /**
     * Determine whether the user can edit others' posts
     */
    public function editOthers(User $user, Post $post): bool
    {
        $can_update = $user->can('update', $post->postType);
        $can_edit_others = $user->can('editOthers', $post->postType);

        // Check department if method exists
        $department_check = true;
        if (method_exists($user, 'isAllowedDepartment') && $post->department_id) {
            $department_check = $user->isAllowedDepartment($post->department);
        }

        return $can_update && ($can_edit_others || $department_check);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->can('delete', $post->postType);
    }

    /**
     * Determine whether the user can view trashed models.
     */
    public function viewTrash(User $user, PostType $post_type): bool
    {
        return $user->can('viewTrash', $post_type);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->can('restore', $post->postType);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->can('forceDelete', $post->postType);
    }

    /**
     * Determine whether the user can view the model logs.
     */
    public function viewLogs(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Determine whether the user can publish the post
     */
    public function publish(User $user, Post $post): bool
    {
        $can_publish = $user->can('publish', $post->postType);
        $can_publish_others = $user->can('publishOthers', $post->postType);

        // Check department if method exists
        $department_check = true;
        if (method_exists($user, 'isAllowedDepartment') && $post->department_id) {
            $department_check = $user->isAllowedDepartment($post->department);
        }

        return $can_publish && ($can_publish_others || $department_check);
    }
}
