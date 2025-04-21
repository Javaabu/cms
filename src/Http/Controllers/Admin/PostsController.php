<?php

namespace Javaabu\Cms\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Javaabu\Cms\Http\Requests\PostRequest;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Helpers\Traits\HasOrderbys;

class PostsController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new  controller instance.
     */
    public function __construct()
    {
        //$this->authorizeResource(Post::class);
    }

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'id' => _d('Id'),
            'created_at' => _d('Created At'),
            'title' => _d('Title'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index($locale, PostType $type, Request $request, bool $trashed = false)
    {
        $this->authorize('viewAny', $type);

        $title = _d('All :type', ['type' => _d($type->name_en)]);
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request);

        $posts = $type->userVisiblePosts()
            ->ofLocale()
            ->orderBy($orderby, $order);

        $search = null;
        if ($search = $request->input('search')) {
            $posts->search($search);
            $title = _d('Posts matching \':search\'', ['search' => $search]);
        }

        if ($primary_language = $request->input('primary_language')) {
            $posts->where('lang', $primary_language);
        }

        if ($request->filled('is_translated')) {
            // TODO: Make json translatable scope
            if ($request->boolean('is_translated')) {
                $posts->whereNotNull('translations');
            } else {
                $posts->whereNull('translations');
            }
        }

        if ($date_field = $request->input('date_field')) {
            $posts->dateBetween($date_field, $request->input('date_from'), $request->input('date_to'));
        }

        if ($department_id = $request->input('department')) {
            $posts->whereDepartmentId($department_id);
        }

        if ($category = $request->input('category')) {
            $posts->belongsToCategory($category);
        }

        if ($status = $request->input('status')) {
            $posts->whereStatus($status);
        }

        if ($trashed) {
            $posts->onlyTrashed();
        }

//        if ($request->download) {
//            return (new PostsExport($posts))->download('posts.xlsx');
//        }

        $posts = $posts->paginate($per_page)
            ->appends($request->except('page'));

        return view('admin.posts.index', compact('posts', 'type', 'title', 'per_page', 'search', 'trashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($locale, PostType $type, Request $request)
    {
        $this->authorize('create', $type);
        return view('admin.posts.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($locale, PostType $type, PostRequest $request)
    {
        $this->authorize('create', $type);

        $post = new Post($request->validated());

        $post->postType()->associate($type);

        if ($action = $request->input('action')) {
            $post->{$action}();
        } elseif ($status = $request->input('status')) {
            $post->updateStatus($status);
        }

        $post->slug = $request->input('slug');

        $post->lang = $request->input('lang', app()->getLocale());

        $post->department()->associate($request->input('department'));


        if ($request->input('never_expire')) {
            $post->expire_at = null;
        }

        if ($type->hasFeature(PostTypeFeatures::PAGE_STYLE) && $request->has('sidebar_menu')) {
            $post->sidebarMenu()->associate($request->input('sidebar_menu'));
        }

        if ($request->input('component')) {
            $post->projectComponent()->associate($request->input('component'));
        }

        if ($type->hasFeature(PostTypeFeatures::COORDS) && $request->hasAny(['lat', 'lng'])) {
            $post->setCoordinates($request->input('lat'), $request->input('lng'));
        }

        if ($type->hasFeature(PostTypeFeatures::CITY) && $request->input('city')) {
            $post->city()->associate($request->input('city'));
        }

        $post->save();

        if ($request->has('sync_categories')) {
            $post->categories()->sync($request->input('categories', []));
        }

        $post->updateSingleAttachment('featured_image', $request);

        if ($request->has('sync_documents')) {
            $post->updateAttachmentMedia($request->input('documents', []), PostTypeFeatures::getCollectionName(PostTypeFeatures::DOCUMENTS));
        }

        if ($request->has('sync_image_gallery')) {
            $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::getCollectionName(PostTypeFeatures::IMAGE_GALLERY));
        }

        if ($request->expectsJson()) {
            return response()->json($post);
        }
        $this->flashSuccessMessage();

        return redirect()->action([PostsController::class, 'edit'], [$locale, $type, $post]);
    }

    /**
     * Display the specified resource.
     */
    public function show($locale, PostType $type, Post $post)
    {
        $this->authorize('view', $post);
        return redirect()->action([static::class, 'edit'], [$locale, $type, $post]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($locale, PostType $type, Post $post)
    {
        $this->authorize('update', $post);
        $post->dontShowTranslationFallbacks();
        return view('admin.posts.edit', compact('post', 'type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($locale, PostRequest $request, PostType $type, Post $post)
    {
        $this->authorize('update', $post);

        // If this is not a translation, set lang
        if ((! $request->input('is_translation')) && $request->input('lang')) {
            $post->lang = $request->input('lang');
            app()->setLocale($post->lang->value);
        }

        $post->fill($request->validated());

        if ($action = $request->input('action')) {
            $post->{$action}();
        } elseif ($status = $request->input('status')) {
            $post->updateStatus($status);
        }

        if ($slug = $request->input('slug')) {
            $post->slug = $slug;
        }

        $post->hide_translation = $request->input('hide_translation', false);
        $post->recently_updated = $request->input('recently_updated', false);

        if ($request->has('department')) {
            $post->department()->associate($request->input('department'));
        }

        if ($request->input('never_expire')) {
            $post->expire_at = null;
        }

        // Should check for feature here?
        if ($type->hasFeature(PostTypeFeatures::PAGE_STYLE) && $request->has('sidebar_menu')) {
            $post->sidebarMenu()->associate($request->input('sidebar_menu'));
        }

        if ($request->input('component')) {
            $post->projectComponent()->associate($request->input('component'));
        }

        if ($type->hasFeature(PostTypeFeatures::COORDS) && $request->hasAny(['lat', 'lng'])) {
            $post->setCoordinates($request->input('lat'), $request->input('lng'));
        }

        if ($type->hasFeature(PostTypeFeatures::CITY) && $request->input('city')) {
            $post->city()->associate($request->input('city'));
        }

        $post->save();

        if ($request->has('sync_tags')) {
            $post->syncTags($request->input('tags', []));
        }

        if ($request->has('sync_categories')) {
            $post->categories()->sync($request->input('categories', []));
        }

        $post->updateSingleAttachment('featured_image', $request);

        // Should check for feature here?
        if ($type->hasFeature(PostTypeFeatures::DOCUMENTS) && $request->has('sync_documents')) {
            $post->updateAttachmentMedia(
                $request->input('documents', []),
                // Use translated collection where necessary
                PostTypeFeatures::getCollectionName(PostTypeFeatures::DOCUMENTS, $post->is_translation)
            );
        }

        if ($type->hasFeature(PostTypeFeatures::RELATED_GALLERIES) && $request->has('sync_related_galleries')) {
            $post->relatedGalleries()->sync($request->input('related_galleries', []));
        }

        // Should check for feature here?
        if ($type->hasFeature(PostTypeFeatures::IMAGE_GALLERY) && $request->has('sync_image_gallery')) {
            $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::getCollectionName(PostTypeFeatures::IMAGE_GALLERY));
        }

        if ($type->hasFeature(PostTypeFeatures::FORMAT) && $request->has('sync_image_gallery')) {
            $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::getCollectionName(PostTypeFeatures::FORMAT));
        }

        $this->flashSuccessMessage();

        return redirect()->action([static::class, 'edit'], [$locale, $type, $post]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($locale, PostType $type, Post $post, Request $request)
    {
        $this->authorize('delete', $post);

        if (! $post->delete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        return redirect()->action([static::class, 'index'], [$locale, $type]);
    }

    /**
     * Display a listing of the deleted resources.
     */
    public function trash($locale, PostType $type, Request $request)
    {
        $this->authorize('viewTrash', $type);

        return $this->index($locale, $type, $request, true);
    }

    /**
     * Force delete the resource
     */
    public function forceDelete($locale, PostType $postType, $id, Request $request)
    {
        //find the model
        $post = $postType->posts()
            ->onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $this->authorize('forceDelete', $post);

        // send error
        if (! $post->forceDelete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        return redirect()->action([static::class, 'trash'], [$locale, $postType]);
    }

    /**
     * Restore deleted resource
     */
    public function restore($locale, PostType $postType, $id, Request $request)
    {
        //find the model
        $post = $postType->posts()
            ->onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $this->authorize('restore', $post);

        // send error
        if (! $post->restore()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        return redirect()->action([static::class, [$locale, $postType]]);
    }

    /**
     * Perform bulk action on the resource
     */
    public function bulk($locale, PostType $type, Request $request)
    {
        $this->authorize('viewAny', $type);

        $this->validate($request, [
            'action'  => 'required|in:delete,publish,draft,markAsPending',
            'posts'   => 'required|array',
            'posts.*' => 'exists:posts,id,type,' . $type->slug,
        ]);

        $action = $request->input('action');
        $ids = $request->input('posts', []);
        $ids_count = count($ids);

        switch ($action) {
            case 'delete':
                //make sure allowed to delete
                $this->authorize('delete', $type);

                $type->posts()
                    ->whereIn('id', $ids)
                    ->userCan('delete', $type)
                    ->get()
                    ->each(function (Post $post) {
                        $post->delete();
                    });
                break;

            case 'reject':
            case 'publish':
            case 'draft':
            case 'markAsPending':
                $posts = $type->posts();

                if ($action == 'draft') {
                    $this->authorize('create', $type);
                    $posts->userCan('edit', $type);
                } else {
                    $this->authorize('publish_' . $type->permission_slug);
                    $posts->userCan('publish', $type);
                }

                $posts->whereIn('id', $ids)
                    ->get()
                    ->each(function (Post $post) use ($action) {
                        $post->{$action}();
                        $post->save();
                    });
                break;
        }

        $this->flashSuccessMessage("{$ids_count} Posts updated!");

        return $this->redirect($request, action([static::class, 'index'], [$locale, $type]));
    }
}
