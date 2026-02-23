<x-forms::table
    model="category_types"
    :no-bulk="! empty($no_bulk)"
    :no-checkbox="! empty($no_checkbox)"
    :no-pagination="! empty($no_pagination)"
>

    @if(empty($no_bulk))
    <x-slot:bulk-form :action="route('admin.category-types.bulk')">
        @include('cms::admin.category-types._bulk')
    </x-slot:bulk-form>
    @endif

    <x-slot:headers>
        <x-forms::table.heading :label="__('Name')" sortable="name" />
        <x-forms::table.heading :label="__('Slug')" sortable="slug" />
        <x-forms::table.heading :label="__('Categories')" />
        <x-forms::table.heading :label="__('Created')" sortable="created_at" />
    </x-slot:headers>

    <x-slot:rows>
        @if($category_types->isEmpty())
            <x-forms::table.empty-row columns="5" :no-checkbox="! empty($no_checkbox)">
                {{ __('No matching category types found.') }}
            </x-forms::table.empty-row>
        @else
            @include('cms::admin.category-types._list')
        @endif
    </x-slot:rows>

    @if(empty($no_pagination))
    <x-slot:pagination>
        {{ $category_types->links('forms::material-admin-26.pagination') }}
    </x-slot:pagination>
    @endif

</x-forms::table>
