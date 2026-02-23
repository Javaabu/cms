<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Cms\Models\PostType;
use Illuminate\Foundation\Auth\User;

class PostTypePolicy
{
    /**
     * Determine whether the user can see view any post types
     */
    public function viewAny(User $user, PostType $post_type): bool
    {
        return $user->can('view_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can view the post type.
     */
    public function view(User $user, PostType $post_type): bool
    {
        return $user->can('view_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can create post type.
     */
    public function create(User $user, PostType $post_type): bool
    {
        return $user->can('edit_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can update the post type.
     */
    public function update(User $user, PostType $post_type): bool
    {
        return $user->can('edit_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can edit others' posts
     */
    public function editOthers(User $user, PostType $post_type): bool
    {
        return $user->can('edit_others_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can delete the post type.
     */
    public function delete(User $user, PostType $post_type): bool
    {
        return $user->can('delete_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can view logs of the post type.
     */
    public function viewLogs(User $user, PostType $post_type): bool
    {
        return $this->update($user, $post_type);
    }

    /**
     * Determine whether the user can publish posts
     */
    public function publish(User $user, PostType $post_type): bool
    {
        return $user->can('publish_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can publish others' posts
     */
    public function publishOthers(User $user, PostType $post_type): bool
    {
        return $user->can('publish_others_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can see the trash
     */
    public function viewTrash(User $user, PostType $postType): bool
    {
        return $user->can('delete_' . $postType->permission_slug) ||
            $user->can('force_delete_' . $postType->permission_slug);
    }

    /**
     * Determine whether the user can restore the post.
     */
    public function restore(User $user, PostType $postType): bool
    {
        return $this->viewTrash($user, $postType);
    }

    /**
     * Determine whether the user can force delete the post.
     */
    public function forceDelete(User $user, PostType $postType): bool
    {
        return $user->can('force_delete_' . $postType->permission_slug);
    }
}
