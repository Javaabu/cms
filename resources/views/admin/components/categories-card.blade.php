@php
    use Illuminate\Support\Str;

    $name = $name ?? 'categories';
    $title = $title ?? __('Categories');
    $disabled = ! empty($disabled);

    $selected_categories = isset($model) ? $model->categories()->pluck('category_id')->all() : old('categories', []);
    if (! is_array($selected_categories)) {
        $selected_categories = [$selected_categories];
    }
    $selected_cats = \Javaabu\Cms\Models\Category::whereTypeId($type->id)
        ->whereIn('id', $selected_categories)
        ->orderBy('name')
        ->get();
    $categories = \Javaabu\Cms\Models\Category::whereTypeId($type->id)
        ->whereNotIn('id', $selected_categories)
        ->orderBy('name')
        ->get();
    $categories = $selected_cats->merge($categories)
                                ->pluck('name', 'id');
    $categories_id = $name.'-list';
@endphp

<x-forms::card :title="$title">
    <div>
        <div class="form-group">
            <div class="input-group mb-0">
                <input type="search" data-filter-checkboxes="{{ '#'.$categories_id }}" class="form-control lang"
                       placeholder="{{ __('Filter..') }}">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <i class="zmdi zmdi-search"></i>
                    </div>
                </div>
                <i class="form-group__bar"></i>
            </div>
        </div>
        <div class="categories-list lang py-3" id="{{ $categories_id }}" data-categories-name="{{ $name }}">
            @foreach($categories as $category_id => $depth_name)
                    <?php $checked = safe_in_array($category_id, $selected_categories) ? ' checked' : ''; ?>
                <div class="checkbox">
                    <input id="{{ $name.'-cat-'.$category_id }}" name="{{ $name }}[]" value="{{ $category_id }}"
                           {{ $disabled ? ' disabled ' : '' }}
                           type="checkbox" {{ $checked }} />
                    <label for="{{ $name.'-cat-'.$category_id }}" class="checkbox__label">
                        {{ $depth_name }}
                    </label>
                </div>
            @endforeach
        </div>
        @include('errors._list', ['error' => $errors->get($name)])
    </div>
    @if(! $disabled)
        <x-forms::hidden :name="'sync_'.$name" :default="1" />
        @can('create', $type)
            @php $categories_add_id = $name.'-add-new'; @endphp
            <a data-toggle-hide={{ '#'.$categories_add_id }} href="#">
                <i class="zmdi zmdi-plus"></i> {{ __('Add New :type', ['type' => Str::singular($title)]) }}
            </a>
            <div id="{{ $categories_add_id }}" class="add-category-form mt-4" style="display: none">
                <div class="form-group mb-4">
                    <x-forms::label name="category_name" :label="__('Category Name')" required />
                    <input type="text" class="form-control lang" name="category_name"
                           placeholder="{{ __('Category Name') }}">
                    <i class="form-group__bar"></i>
                </div>
                <div class="form-group mb-4">
                    <x-forms::label name="category_slug" :label="__('Category Slug')" required />
                    <input type="text" class="form-control" name="category_slug"
                           placeholder="{{ __('Category Slug') }}">
                    <i class="form-group__bar"></i>
                </div>
                <a href="{{ translate_route('admin.categories.store', [$type]) }}"
                   class="add-category btn btn-info btn--icon-text">
                    <i class="zmdi zmdi-plus"></i> {{ __('Add New :type', ['type' => Str::singular($title)]) }}
                </a>
            </div>
        @endcan
    @endif
</x-forms::card>
