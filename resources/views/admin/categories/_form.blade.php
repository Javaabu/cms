@php
    $is_editing = isset($category);
@endphp

<div class="row">
    <div class="col-md-8">
        <x-forms::card :title="__('General Information')">
            <x-forms::text
                name="name"
                :label="__('Name')"
                :placeholder="__('Category Name')"
                required
            />

            <x-forms::text
                name="slug"
                :label="__('Slug')"
                :placeholder="__('category-slug')"
                :help="__('URL-friendly identifier (lowercase, hyphens only)')"
                required
            />

            @php
                $selected_parent = isset($category) ? $category->parent_id : old('parent');
                $type_id = isset($type) ? $type->id : null;
                $category_id = isset($category) ? $category->id : null;
                $parents = isset($allowed_categories) ? ['' => __('None (Root Level)')] + $allowed_categories :
                    \Javaabu\Cms\Models\Category::categoryList($type_id, $category_id);
            @endphp
            <x-forms::select2
                name="parent"
                :label="__('Parent Category')"
                :options="$parents"
                :default="$selected_parent"
                :placeholder="__('Select Parent Category')"
                allow-clear
            />

            <x-forms::textarea
                name="description"
                :label="__('Description')"
                :placeholder="__('Category description...')"
                rows="4"
            />
        </x-forms::card>
    </div>

    <div class="col-md-4">
        <x-forms::card :title="__('Settings')">
            <x-forms::text
                name="icon"
                :label="__('Icon Class')"
                :placeholder="__('zmdi zmdi-icon-name')"
                :help="__('Material Design icon class')"
            />

            <x-forms::text
                name="statistic_type"
                :label="__('Statistic Type')"
                :placeholder="__('Statistic type identifier')"
            />

            <x-forms::number
                name="order_column"
                :label="__('Order')"
                :placeholder="__('0')"
                :help="__('Display order (lower numbers appear first)')"
            />
        </x-forms::card>
    </div>
</div>

<x-forms::button-group inline>
    <x-forms::submit color="success" class="btn--icon-text btn--raised">
        <i class="zmdi zmdi-check"></i> {{ __('Save') }}
    </x-forms::submit>

    <x-forms::link-button color="light" class="btn--icon-text" :url="route('admin.categories.index', $type)">
        <i class="zmdi zmdi-close-circle"></i> {{ __('Cancel') }}
    </x-forms::link-button>
</x-forms::button-group>

@include('admin.components.floating-submit')

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#slug').keypress(function (e) {
                return restrictCharacters($(this), e, /[ -A-Za-z0-9]/g);
            });

            @unless($is_editing)
            $('#name').keyup(function () {
                var str = $(this).val();
                $('#slug').val(slugify(str, '-'));
            });
            @endunless
        });
    </script>
@endpush
