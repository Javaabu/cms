<?php

namespace Javaabu\Cms\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Cms\Http\Requests\CategoriesRequest;
use Javaabu\Cms\Models\Category;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Helpers\Traits\HasOrderbys;

class CategoriesController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new  controller instance.
     */
    public function __construct()
    {
        //$this->authorizeResource(Category::class);
    }

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'id' => _d('Id'),
            'created_at' => _d('Created At'),
            'updated_at' => _d('Updated At'),
            'name' => _d('Name')
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index($locale, CategoryType $type, Request $request)
    {
        $this->authorize('viewAny', $type);

        $title = _d('All Categories');
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
            $title = _d('Categories matching \':search\'', ['search' => $search]);
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

        $categories->with('type.postType')
                   ->withCount('posts', 'staffs', 'statistics');

        if ($request->download) {
            return (new CategoriesExport($categories))->download('categories.xlsx');
        }

        $categories = $categories->paginate($per_page)
                                 ->appends($request->except('page'));

        return view('admin.categories.index', compact('categories', 'type', 'title', 'per_page', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($locale, CategoryType $type, Request $request)
    {
        $this->authorize('create', $type);

        return view('admin.categories.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($locale, CategoryType $type, CategoriesRequest $request)
    {
        $this->authorize('create', $type);

        $category = new Category($request->validated());

        $category->type()->associate($type);

        $category->slug = $request->input('slug');

        $category->lang = $request->input('lang', app()->getLocale());

        if ($request->has('icon')) {
            $category->icon = $request->input('icon');
        }

        if ($request->has('color')) {
            $category->color = $request->input('color');
        }

        $category->save();

        $category->updateSingleAttachment('featured_image', $request);

        if ($request->expectsJson()) {
            return response()->json($category);
        }

        $this->flashSuccessMessage();

        return redirect()->action([CategoriesController::class, 'edit'], [$locale, $type, $category]);
    }

    /**
     * Display the specified resource.
     */
    public function show($locale, CategoryType $type, Category $category)
    {
        $this->authorize('view', $category);

        return redirect()->action([CategoriesController::class, 'edit'], [$locale, $type, $category]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($locale, CategoryType $type, Category $category)
    {
        $this->authorize('update', $category);

        $allowed_categories = Category::categoryList($type->id, $category->id);
//        $category->dontShowTranslationFallbacks();
        return view('admin.categories.edit', compact('category', 'type', 'allowed_categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($locale, CategoryType $type, CategoriesRequest $request, Category $category)
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

        if ($request->has('icon')) {
            $category->icon = $request->input('icon');
        }

        if ($request->has('color')) {
            $category->color = $request->input('color');
        }

        $category->hide_translation = $request->input('hide_translation', false);

        $category->save();

        $category->updateSingleAttachment('featured_image', $request);

        $this->flashSuccessMessage();

        return redirect()->action([CategoriesController::class, 'edit'], [$locale, $type, $category]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($locale, CategoryType $type, Category $category, Request $request)
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

        return redirect()->action([CategoriesController::class, 'index'], [$locale, $type]);
    }

    /**
     * Perform bulk action on the resource
     */
    public function bulk($locale, CategoryType $type, Request $request)
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

        $this->flashSuccessMessage();

        return $this->redirect($request, action([CategoriesController::class, 'index'], [$locale, $type]));
    }
}
