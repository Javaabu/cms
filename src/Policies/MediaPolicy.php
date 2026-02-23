<?php

namespace Javaabu\Cms\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPolicy
{

    /**
     * Determine whether the user can see all media
     *
     * @param User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_media');
    }

    /**
     * Determine whether the user can view the media.
     *
     * @param User $user
     * @param Media $media
     * @return mixed
     */
    public function view(User $user, Media $media)
    {
        return $this->update($user, $media);
    }

    /**
     * Determine whether the user can update the media.
     *
     * @param User $user
     * @param Media $media
     * @return mixed
     */
    public function update(User $user, Media $media)
    {
        return true;
        return $user->canDo(\Javaabu\Cms\Media\Media::class);
    }

    /**
     * Determine whether the user can create media.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('edit_media');
    }

    /**
     * Determine whether the user can delete the media.
     *
     * @param User $user
     * @param Media $media
     * @return mixed
     */
    public function delete(User $user, Media $media)
    {
        $own_media = $media->model_type == $user->getMorphClass() && $media->model_id == $user->id;

        if ($user instanceof User && $user->can('delete_media')) {
            return $user->can('delete_other_users_media') || $own_media;
        }

        return false;
    }
}





