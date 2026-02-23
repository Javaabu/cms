@php
    $is_editing = isset($category_type);
@endphp

<div class="row">
    <div class="col-md-8">
        <x-forms::card :title="__('General Information')">
            <x-forms::text
                name="name"
                :label="__('Name')"
                :placeholder="__('Category Type Name')"
                required
            />

            <x-forms::text
                name="singular_name"
                :label="__('Singular Name')"
                :placeholder="__('Category Type Singular Name')"
                required
            />

            <x-forms::text
                name="slug"
                :label="__('Slug')"
                :placeholder="__('category-type-slug')"
                :help="__('URL-friendly identifier (lowercase, hyphens only)')"
                required
            />
        </x-forms::card>
    </div>

    <div class="col-md-4">
        <x-forms::card :title="__('Settings')">
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

    <x-forms::link-button color="light" class="btn--icon-text" :url="route('admin.category-types.index')">
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
