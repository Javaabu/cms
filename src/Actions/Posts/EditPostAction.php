<?php

use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;

class EditPostAction {
    public function handle(Post $post, PostRequest $request)
    {
        $post->update($request->validated());

        if ($request->hasFile('media')) {
            $post->addMedia($request->file('media'))->toMediaCollection('media');
        }

        return $post;
    }
}
