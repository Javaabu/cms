<?php

namespace Javaabu\Cms\Translatable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Http\Controllers\Controller;

class PostsController extends Controller
{
    /**
     * Show the application dashboard
     */
    public function index($post_type, Request $request)
    {
        $post_type = PostType::whereSlug($post_type)->first();
        $postModel = config('cms.models.post', Post::class);

        $title = $post_type->title;
        $per_page = $post_type->getPaginatorCount();

        $category = $request->input('category');

        $posts = $postModel::query()
            ->postType($post_type)
            ->published();

        if ($category && method_exists($posts->getModel(), 'scopeBelongsToCategory')) {
            $posts->belongsToCategory($category);
        }

        if (method_exists($posts->getModel(), 'scopeOfLocale')) {
            $posts->ofLocale();
        }

        if (
            method_exists($posts->getModel(), 'scopeNotHiddenOfLocale')
            && Schema::hasColumn($posts->getModel()->getTable(), 'hide_translation')
        ) {
            $posts->notHiddenOfLocale();
        }

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

    public function show(Request $request, Post $post, PostType $post_type)
    {
        $post->loadMissing(['postType', 'attachments.media', 'categories']);

        $post_documents = $this->getPostDocuments($post);
        $related_posts = $this->getRelatedPosts($post, $post_type);

        return view(
            $post_type->getWebView('show'),
            compact('post', 'post_type', 'post_documents', 'related_posts')
        );
    }

    protected function getPostDocuments(Post $post): Collection
    {
        if (! method_exists($post, 'getAttachmentMedia')) {
            return collect();
        }

        $translatedDocuments = $post->getAttachmentMedia(PostTypeFeatures::DOCUMENTS->getCollectionName(true)) ?? collect();

        if ($translatedDocuments->isNotEmpty()) {
            return $translatedDocuments;
        }

        return $post->getAttachmentMedia(PostTypeFeatures::DOCUMENTS->getCollectionName(false)) ?? collect();
    }

    protected function getRelatedPosts(Post $post, PostType $postType): Collection
    {
        $limit = method_exists($postType, 'getRelatedPostsCount')
            ? $postType->getRelatedPostsCount()
            : 5;

        if (method_exists($post, 'similarByTag')) {
            return $post->similarByTag()
                ->published()
                ->with(['postType', 'attachments.media'])
                ->latest('published_at')
                ->limit($limit)
                ->get();
        }

        if (method_exists($post, 'similarByCategory')) {
            return $post->similarByCategory()
                ->with(['postType', 'attachments.media'])
                ->limit($limit)
                ->get();
        }

        return collect();
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
//    public function downloadFiles(Request $request, Post $post, PostType $post_type): MediaStream
//    {
//        $media_ids = $post->attachments_for_translation->pluck('media_id');
//        $media = Media::whereIn('id', $media_ids)->get();
//        return MediaStream::create("$post->title.zip")->addMedia($media);
//    }
}
