<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Auth\User;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;

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
        return $user->can('viewAny', $post->post_type);
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
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->can('delete', $post->postType);
    }

    /**
     * Determine whether the user can view trashed models.
     */
    public function viewTrash(User $user): bool
    {
        return $user->can('delete_posts') || $user->can('force_delete_posts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->can('delete_posts') || $user->can('force_delete_posts');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->can('forceDelete', $post->postType);
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->can('publish', $post->postType);
    }
}
