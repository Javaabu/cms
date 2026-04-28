<?php

namespace Javaabu\Cms\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Cms\Models\Post;
use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Helpers\Traits\HasOrderbys;
use Javaabu\Cms\Http\Requests\PostsRequest;
use Javaabu\Cms\Enums\PostTypeFeatures;

class PostsController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Authorization handled per-method due to PostType binding
    }

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'id' => __('Id'),
            'created_at' => __('Created At'),
            'title' => __('Title'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PostType $type, Request $request, bool $trashed = false)
    {
        $this->authorize('viewAny', $type);

        $title = __('All :type', ['type' => $type->name]);
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request);

        $posts = $type->posts()
            ->orderBy($orderby, $order);

        $search = null;
        if ($search = $request->input('search')) {
            $posts->search($search);
            $title = __('Posts matching \':search\'', ['search' => $search]);
        }

        if ($primary_language = $request->input('primary_language')) {
            $posts->where('lang', $primary_language);
        }

        if ($request->filled('is_translated')) {
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

        if (method_exists($posts, 'with')) {
            $posts->with('department');
        }

        $posts = $posts->paginate($per_page)
            ->appends($request->except('page'));

        return view('cms::admin.posts.index', compact('posts', 'type', 'title', 'per_page', 'search', 'trashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(PostType $type, Request $request)
    {
        $this->authorize('create', $type);
        return view('cms::admin.posts.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostType $type, PostsRequest $request)
    {
        $this->authorize('create', $type);

        $post = new Post($request->validated());

        $post->postType()->associate($type);

        if ($action = $request->input('action')) {
            if (method_exists($post, $action)) {
                $post->{$action}();
            }
        } elseif ($status = $request->input('status')) {
            if (method_exists($post, 'updateStatus')) {
                $post->updateStatus($status);
            }
        }

        $post->slug = $request->input('slug');

        $post->lang = $request->input('lang', app()->getLocale());

        if ($request->has('department') && method_exists($post, 'department') && \Illuminate\Support\Facades\Schema::hasColumn($post->getTable(), 'department_id')) {
            $post->department()->associate($request->input('department'));
        }

        if ($request->input('never_expire')) {
            $post->expire_at = null;
        }

        if ($type->hasFeature(PostTypeFeatures::PAGE_STYLE) && $request->has('sidebar_menu')) {
            if (method_exists($post, 'sidebarMenu')) {
                $post->sidebarMenu()->associate($request->input('sidebar_menu'));
            }
        }

        $post->save();

        if ($request->has('sync_categories') && method_exists($post, 'categories')) {
            $post->categories()->sync($request->input('categories', []));
        }

        if (method_exists($post, 'updateSingleAttachment')) {
            $post->updateSingleAttachment('featured_image', $request);
        }

        if ($request->has('sync_documents') && method_exists($post, 'updateAttachmentMedia')) {
            $post->updateAttachmentMedia($request->input('documents', []), PostTypeFeatures::DOCUMENTS->getCollectionName());
        }

        if ($request->has('sync_image_gallery') && method_exists($post, 'updateAttachmentMedia')) {
            $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::IMAGE_GALLERY->getCollectionName());
        }

        if ($request->expectsJson()) {
            return response()->json($post);
        }

        $this->flashSuccessMessage(__('Post successfully created!'));

        return redirect()->route('admin.posts.edit', [$type, $post]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostType $type, Post $post)
    {
        $this->authorize('view', $post);
        return redirect()->route('admin.posts.edit', [$type, $post]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PostType $type, Post $post)
    {
        $this->authorize('update', $post);
        if (method_exists($post, 'dontShowTranslationFallbacks')) {
            $post->dontShowTranslationFallbacks();
        }
        return view('cms::admin.posts.edit', compact('post', 'type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostsRequest $request, PostType $type, Post $post)
    {
        $this->authorize('update', $post);

        // If this is not a translation, set lang
        if (config('cms.should_translate') && (!$request->input('is_translation')) && $request->input('lang')) {
            $post->lang = $request->input('lang');
            $lang = $post->lang;
            app()->setLocale($lang instanceof \BackedEnum ? $lang->value : $lang);
        }

        $post->fill($request->validated());

        if ($action = $request->input('action')) {
            if (method_exists($post, $action)) {
                $post->{$action}();
            }
        } elseif ($status = $request->input('status')) {
            if (method_exists($post, 'updateStatus')) {
                $post->updateStatus($status);
            }
        }

        if ($slug = $request->input('slug')) {
            $post->slug = $slug;
        }

        $post->hide_translation = $request->input('hide_translation', false);
        $post->recently_updated = $request->input('recently_updated', false);

        if ($request->has('department') && method_exists($post, 'department') && \Illuminate\Support\Facades\Schema::hasColumn($post->getTable(), 'department_id')) {
            $post->department()->associate($request->input('department'));
        }

        if ($request->input('never_expire')) {
            $post->expire_at = null;
        }

        if ($type->hasFeature(PostTypeFeatures::PAGE_STYLE) && $request->has('sidebar_menu')) {
            if (method_exists($post, 'sidebarMenu')) {
                $post->sidebarMenu()->associate($request->input('sidebar_menu'));
            }
        }

        $post->save();

        if ($request->has('sync_tags') && method_exists($post, 'syncTags')) {
            $post->syncTags($request->input('tags', []));
        }

        if ($request->has('sync_categories') && method_exists($post, 'categories')) {
            $post->categories()->sync($request->input('categories', []));
        }

        if (method_exists($post, 'updateSingleAttachment')) {
            $post->updateSingleAttachment('featured_image', $request);
        }

        if ($type->hasFeature(PostTypeFeatures::DOCUMENTS) && $request->has('sync_documents')) {
            if (method_exists($post, 'updateAttachmentMedia')) {
                $post->updateAttachmentMedia(
                    $request->input('documents', []),
                    PostTypeFeatures::DOCUMENTS->getCollectionName($post->is_translation ?? false)
                );
            }
        }

        if ($type->hasFeature(PostTypeFeatures::RELATED_GALLERIES) && $request->has('sync_related_galleries')) {
            if (method_exists($post, 'relatedGalleries')) {
                $post->relatedGalleries()->sync($request->input('related_galleries', []));
            }
        }

        if ($type->hasFeature(PostTypeFeatures::IMAGE_GALLERY) && $request->has('sync_image_gallery')) {
            if (method_exists($post, 'updateAttachmentMedia')) {
                $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::IMAGE_GALLERY->getCollectionName());
            }
        }

        if ($type->hasFeature(PostTypeFeatures::FORMAT) && $request->has('sync_image_gallery')) {
            if (method_exists($post, 'updateAttachmentMedia')) {
                $post->updateAttachmentMedia($request->input('image_gallery', []), PostTypeFeatures::FORMAT->getCollectionName());
            }
        }

        $this->flashSuccessMessage(__('Post successfully updated!'));

        return redirect()->route('admin.posts.edit', [$type, $post]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostType $type, Post $post, Request $request)
    {
        $this->authorize('delete', $post);

        if (!$post->delete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        $this->flashSuccessMessage(__('Post successfully deleted!'));

        return redirect()->route('admin.posts.index', $type);
    }

    /**
     * Display a listing of the deleted resources.
     */
    public function trash(PostType $type, Request $request)
    {
        $this->authorize('viewTrash', $type);

        return $this->index($type, $request, true);
    }

    /**
     * Force delete the resource
     */
    public function forceDelete(PostType $postType, $id, Request $request)
    {
        //find the model
        $post = $postType->posts()
            ->onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $this->authorize('forceDelete', $post);

        // send error
        if (!$post->forceDelete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        $this->flashSuccessMessage(__('Post permanently deleted!'));

        return redirect()->route('admin.posts.trash', $postType);
    }

    /**
     * Restore deleted resource
     */
    public function restore(PostType $postType, $id, Request $request)
    {
        //find the model
        $post = $postType->posts()
            ->onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $this->authorize('restore', $post);

        // send error
        if (!$post->restore()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        $this->flashSuccessMessage(__('Post successfully restored!'));

        return redirect()->route('admin.posts.index', $postType);
    }

    /**
     * Perform bulk action on the resource
     */
    public function bulk(PostType $type, Request $request)
    {
        $this->authorize('viewAny', $type);

        $this->validate($request, [
            'action' => 'required|in:delete,publish,draft,markAsPending',
            'posts' => 'required|array',
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
                    ->get()
                    ->each(function (Post $post) {
                        if (auth()->user()->can('delete', $post)) {
                            $post->delete();
                        }
                    });
                break;

            case 'reject':
            case 'publish':
            case 'draft':
            case 'markAsPending':
                $posts = $type->posts();

                if ($action == 'draft') {
                    $this->authorize('create', $type);
                } else {
                    $this->authorize('publish', $type);
                }

                $posts->whereIn('id', $ids)
                    ->get()
                    ->each(function (Post $post) use ($action, $type) {
                        if (method_exists($post, $action)) {
                            if ($action == 'draft' && auth()->user()->can('update', $post)) {
                                $post->{$action}();
                                $post->save();
                            } elseif ($action != 'draft' && auth()->user()->can('publish', $type)) {
                                $post->{$action}();
                                $post->save();
                            }
                        }
                    });
                break;
        }

        $this->flashSuccessMessage(__(':count Posts updated!', ['count' => $ids_count]));

        return redirect()->route('admin.posts.index', $type);
    }
}
