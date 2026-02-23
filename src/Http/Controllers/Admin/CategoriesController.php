<?php

namespace Javaabu\Cms\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Helpers\Traits\HasOrderbys;
use Javaabu\Cms\Http\Requests\CategoriesRequest;

class CategoriesController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Authorization handled per-method due to CategoryType binding
    }

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'id' => __('Id'),
            'created_at' => __('Created At'),
            'updated_at' => __('Updated At'),
            'name' => __('Name'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CategoryType $type, Request $request)
    {
        $this->authorize('viewAny', $type);

        $title = __('All Categories');
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request);

        $categories = $type->categories()
                           ->withDepth()
                           ->defaultOrder()
                           ->orderBy($orderby, $order);

        $search = null;
        if ($search = $request->input('search')) {
            $categories->search($search);
            $title = __('Categories matching \':search\'', ['search' => $search]);
        }

        if ($primary_language = $request->input('primary_language')) {
            $categories->whereLang($primary_language);
        }

        if (! is_null($request->input('is_translated')) && $request->has('is_translated')) {
            if ($request->boolean('is_translated')) {
                $categories->whereNotNull('translations');
            } else {
                $categories->whereNull('translations');
            }
        }

        $categories = $categories->paginate($per_page)
                                 ->appends($request->except('page'));

        return view('cms::admin.categories.index', compact('categories', 'type', 'title', 'per_page', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CategoryType $type, Request $request)
    {
        $this->authorize('create', $type);

        return view('cms::admin.categories.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryType $type, CategoriesRequest $request)
    {
        $this->authorize('create', $type);

        $category = new Category($request->validated());

        $category->type()->associate($type);

        $category->slug = $request->input('slug');

        $category->lang = $request->input('lang', app()->getLocale());

        $category->save();

        if (method_exists($category, 'updateSingleAttachment')) {
            $category->updateSingleAttachment('featured_image', $request);
        }

        if ($request->expectsJson()) {
            return response()->json($category);
        }

        $this->flashSuccessMessage(__('Category successfully created!'));

        return redirect()->route('admin.categories.edit', [$type, $category]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CategoryType $type, Category $category)
    {
        $this->authorize('view', $category);

        return redirect()->route('admin.categories.edit', [$type, $category]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CategoryType $type, Category $category)
    {
        $this->authorize('update', $category);

        $allowed_categories = Category::categoryList($type->id, $category->id);
        $category->dontShowTranslationFallbacks();

        return view('cms::admin.categories.edit', compact('category', 'type', 'allowed_categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryType $type, CategoriesRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category->fill($request->validated());

        if ($slug = $request->input('slug')) {
            $category->slug = $slug;
        }

        if ($request->has('parent')) {
            if ($parent = $request->input('parent')) {
                $category->parent()->associate($parent);
            } else {
                $category->makeRoot();
            }
        }

        $category->hide_translation = $request->input('hide_translation', false);

        $category->save();

        if (method_exists($category, 'updateSingleAttachment')) {
            $category->updateSingleAttachment('featured_image', $request);
        }

        flash(__('Category successfully updated!'))->success();

        return redirect()->route('admin.categories.edit', [$type, $category]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryType $type, Category $category, Request $request)
    {
        $this->authorize('delete', $category);

        if (! $category->delete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        $this->flashSuccessMessage(__('Category successfully updated!'));

        return redirect()->route('admin.categories.index', $type);
    }

    /**
     * Perform bulk action on the resource
     */
    public function bulk(CategoryType $type, Request $request)
    {
        $this->authorize('viewAny', $type);

        $this->validate($request, [
            'action' => 'required|in:delete',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id,type_id,' . $type->id,
        ]);

        $action = $request->input('action');
        $ids = $request->input('categories', []);

        switch ($action) {
            case 'delete':
                // make sure allowed to delete
                $this->authorize('delete', $type);

                $type->categories()->whereIn('id', $ids)
                     ->get()
                     ->each(function (Category $category) {
                         $category->delete();
                     });
                break;
        }

        $this->flashSuccessMessage(__('Bulk action completed successfully!'));

        return redirect()->route('admin.categories.index', $type);
    }
}
