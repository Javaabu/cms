<?php

namespace Javaabu\Cms\Translatable\Http\Controllers;

use Illuminate\Http\Request;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Http\Controllers\Controller;

class PostsController extends Controller
{
    /**
     * Show the application dashboard
     */
    public function index($lang, $post_type, Request $request)
    {
        $post_type = PostType::whereSlug($post_type)->first();

        $title = $post_type->title;
        $per_page = $post_type->getPaginatorCount();

        $category = $request->input('category');

        $posts = $post_type->posts()
            ->belongsToCategory($category)
            ->ofLocale()
            ->notHiddenOfLocale()
            ->published();

        if ($search = $request->input('search')) {
            $posts->search($search);
            $title = __(':post_type matching \':search\'', [
                'search'    => $search,
                'post_type' => $post_type->name,
            ]);
        }

        if ($year = $request->input('year')) {
            $posts->publishedByYear($year);
        }

        if ($department = $request->input('department')) {
            $posts->whereDepartmentId($department);
        }

        if ($component = $request->input('component')) {
            $posts->whereComponentId($component);
        }

        if ($status = $request->input('expiry_status')) {
            if ($status == 'expired') {
                $posts->expired();
            }

            if ($status == 'active') {
                $posts->notExpired();
            }
        }

        $posts = $posts->with(['postType', 'categories'])
            ->latest('published_at')
            ->paginate($per_page)
            ->onEachSide(1)
            ->appends($request->except('page'));


        return view($post_type->getWebView(), compact('posts', 'post_type', 'title', 'search'));
    }

    public function show($lang, Request $request, Post $post, PostType $post_type)
    {
        // Load up relations
        $post->load(['department', 'attachments', 'attachments.media']);

        $post_documents = $post->attachments_for_translation;

        $related_posts = $post_type->posts()
            ->similarToTags($post)
            ->published()
            ->withRelations()
            ->orderBy('tag_similarity', 'DESC')
            ->latest('published_at')
            ->limit($post_type->getRelatedPostsCount())
            ->get();

        // Need to load full models. Above statement only gives specific fields.
        $related_posts = Post::whereIn('id', $related_posts->pluck('id'))
            ->with('postType', 'attachments.media')
            ->get();

        return view(
            $post_type->getWebView('show'),
            compact('post', 'post_type', 'post_documents', 'related_posts')
        );
    }

//    /**
//     * Download files as zip method.
//     *
//     * @param            $lang
//     * @param Request $request
//     * @param Post $post
//     * @param PostType $post_type
//     * @return MediaStream
//     */
//    public function downloadFiles($lang, Request $request, Post $post, PostType $post_type): MediaStream
//    {
//        $media_ids = $post->attachments_for_translation->pluck('media_id');
//        $media = Media::whereIn('id', $media_ids)->get();
//        return MediaStream::create("$post->title.zip")->addMedia($media);
//    }
}
