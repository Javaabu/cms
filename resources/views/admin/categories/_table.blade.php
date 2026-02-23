<x-forms::table
    model="categories"
    :no-bulk="! empty($no_bulk)"
    :no-checkbox="! empty($no_checkbox)"
    :no-pagination="! empty($no_pagination)"
>

    @if(empty($no_bulk))
    <x-slot:bulk-form :action="route('admin.categories.bulk', $type)">
        @include('cms::admin.categories._bulk')
    </x-slot:bulk-form>
    @endif

    <x-slot:headers>
        <x-forms::table.heading :label="__('Name')" sortable="name" />
        <x-forms::table.heading :label="__('Slug')" sortable="slug" />
        <x-forms::table.heading :label="__('Parent')" />
        <x-forms::table.heading :label="__('Posts')" />
    </x-slot:headers>

    <x-slot:rows>
        @if($categories->isEmpty())
            <x-forms::table.empty-row columns="5" :no-checkbox="! empty($no_checkbox)">
                {{ __('No matching categories found.') }}
            </x-forms::table.empty-row>
        @else
            @include('cms::admin.categories._list')
        @endif
    </x-slot:rows>

    @if(empty($no_pagination))
    <x-slot:pagination>
        {{ $categories->links('forms::material-admin-26.pagination') }}
    </x-slot:pagination>
    @endif

</x-forms::table>
