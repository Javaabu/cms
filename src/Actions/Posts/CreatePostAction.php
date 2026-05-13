<?php

namespace Javaabu\Cms\Actions\Posts;

use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;

class CreatePostAction {
    public function handle(PostRequest $request)
    {
        $validated = $request->validated();

        $post = new Post($validated);
        if (array_key_exists('lang', $validated)) {
            $post->lang = $validated['lang'];
        }
        $post->save();

        return $post;
    }
}
