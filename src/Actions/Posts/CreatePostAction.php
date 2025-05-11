<?php

namespace Javaabu\Cms\Actions\Posts;

use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;

class CreatePostAction {
    public function handle(PostRequest $request)
    {
        $post = Post::create($request->validated());

        return $post;
    }
}
