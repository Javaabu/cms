<?php

namespace Javaabu\Cms\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Cms\Models\CategoryType;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Helpers\Traits\HasOrderbys;
use Javaabu\Cms\Http\Requests\CategoryTypesRequest;

class CategoryTypesController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->authorizeResource(CategoryType::class, 'category_type');
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
            'slug' => __('Slug'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $title = __('All Category Types');
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request);

        $category_types = CategoryType::orderBy($orderby, $order);

        $search = null;
        if ($search = $request->input('search')) {
            $category_types->search($search);
            $title = __('Category Types matching \':search\'', ['search' => $search]);
        }

        if ($date_field = $request->input('date_field')) {
            $category_types->dateBetween($date_field, $request->input('date_from'), $request->input('date_to'));
        }

        $category_types = $category_types->paginate($per_page)
                                         ->appends($request->except('page'));

        return view('cms::admin.category-types.index', compact('category_types', 'title', 'per_page', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('cms::admin.category-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryTypesRequest $request)
    {
        $category_type = new CategoryType($request->validated());

        if (config('cms.should_translate')) {
            $category_type->lang = $request->input('lang', app()->getLocale());
        }

        $category_type->save();

        $this->flashSuccessMessage(__('Category Type successfully created!'));

        return redirect()->route('admin.category-types.edit', $category_type);
    }

    /**
     * Display the specified resource.
     */
    public function show(CategoryType $category_type)
    {
        return redirect()->route('admin.category-types.edit', $category_type);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CategoryType $category_type)
    {
        if (method_exists($category_type, 'dontShowTranslationFallbacks')) {
            $category_type->dontShowTranslationFallbacks();
        }
        return view('cms::admin.category-types.edit', compact('category_type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryTypesRequest $request, CategoryType $category_type)
    {
        if (config('cms.should_translate')) {
            // If this is not a translation, set lang
            if ((! $request->input('is_translation')) && $request->input('lang')) {
                $category_type->lang = $request->input('lang');
                $lang = $category_type->lang;
                app()->setLocale($lang instanceof \BackedEnum ? $lang->value : $lang);
            }
        }

        $category_type->fill($request->validated());

        if ($slug = $request->input('slug')) {
            $category_type->slug = $slug;
        }

        if (config('cms.should_translate') && method_exists($category_type, 'hide_translation')) {
            $category_type->hide_translation = $request->input('hide_translation', false);
        }

        $category_type->save();

        $this->flashSuccessMessage(__('Category Type successfully updated!'));

        return redirect()->route('admin.category-types.edit', $category_type);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryType $category_type, Request $request)
    {
        if (! $category_type->delete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }

            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        $this->flashSuccessMessage(__('Category Type successfully deleted!'));

        return redirect()->route('admin.category-types.index');
    }

    /**
     * Perform bulk action on the resource
     */
    public function bulk(Request $request)
    {
        $this->authorize('viewAny', CategoryType::class);

        $this->validate($request, [
            'action' => 'required|in:delete',
            'category_types' => 'required|array',
            'category_types.*' => 'exists:category_types,id',
        ]);

        $action = $request->input('action');
        $ids = $request->input('category_types', []);
        $ids_count = count($ids);

        switch ($action) {
            case 'delete':
                // make sure allowed to delete
                $this->authorize('delete', CategoryType::class);

                CategoryType::whereIn('id', $ids)
                    ->get()
                    ->each(function (CategoryType $category_type) {
                        $category_type->delete();
                    });
                break;
        }

        $this->flashSuccessMessage(__(':count Category Types updated!', ['count' => $ids_count]));

        return redirect()->route('admin.category-types.index');
    }
}
