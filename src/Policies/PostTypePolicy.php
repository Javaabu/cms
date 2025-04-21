<?php

namespace Javaabu\Cms\Policies;

use Javaabu\Auth\User;
use Javaabu\Cms\Models\PostType;

class PostTypePolicy
{
    /**
     * Determine whether the user can see view any post types
     *
     * @param User $user
     * @param PostType $post_type
     * @return bool
     */
    public function viewAny(User $user, PostType $post_type): bool
    {
        return $user->can('view_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can view the post type.
     *
     * @param User $user
     * @param PostType $post_type
     * @return bool
     */
    public function view(User $user, PostType $post_type): bool
    {
        return $user->can('view_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can create post type.
     *
     * @param User $user
     * @param PostType $post_type
     * @return bool.
     */
    public function create(User $user, PostType $post_type): bool
    {
        return $user->can('edit_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can delete the post type.
     *
     * @param User $user
     * @param PostType $post_type
     * @return mixed
     */
    public function delete(User $user, PostType $post_type)
    {
        return $user->can('delete_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can update the post type.
     *
     * @param User $user
     * @param PostType $post_type
     * @return bool
     */
    public function update(User $user, PostType $post_type): bool
    {
        return $user->can('edit_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can publish news
     *
     * @param User $user
     * @param PostType $post_type
     * @return bool
     */
    public function publish(User $user, PostType $post_type): bool
    {
        return $user->can('publish_' . $post_type->permission_slug);
    }

    /**
     * Determine whether the user can restore the post.
     *
     * @param User $user
     * @param PostType $postType
     * @return bool
     */
    public function restore(User $user, PostType $postType): bool
    {
        return $this->trash($user, $postType);
    }

    /**
     * Determine whether the user can see the trash
     *
     * @param User $user
     * @param PostType $postType
     * @return bool
     */
    public function trash(User $user, PostType $postType): bool
    {
        return $user->can('delete_' . $postType->permission_slug) ||
            $user->can('force_delete_' . $postType->permission_slug);
    }

    /**
     * Determine whether the user can force delete the download.
     *
     * @param User $user
     * @param PostType $postType
     * @return bool
     */
    public function forceDelete(User $user, PostType $postType): bool
    {
        return $user->can('force_delete_' . $postType->permission_slug);
    }

}
