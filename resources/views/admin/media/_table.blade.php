<x-forms::table
    model="media"
    :no-bulk="! empty($no_bulk)"
    :no-checkbox="! empty($no_checkbox)"
    :no-pagination="! empty($no_pagination)"
>

    @if(empty($no_bulk))
        <x-slot:bulk-form :action="translate_route('admin.media.bulk')">
            @include('cms::admin.media._bulk')
        </x-slot:bulk-form>
    @endif

        <x-slot:headers>
            <x-forms::table.heading :label="__('Name')" sortable="name" />
            <x-forms::table.heading :label="__('Model')" />
            <x-forms::table.heading :label="__('Uploaded')" sortable="created_at" />
        </x-slot:headers>

        <x-slot:rows>
            @if($media_items->isEmpty())
                <x-forms::table.empty-row columns="3" :no-checkbox="! empty($no_checkbox)">
                    {{ __('No matching media found.') }}
                </x-forms::table.empty-row>
            @else
                @include('cms::admin.media._list')
            @endif
        </x-slot:rows>

        @if(empty($no_pagination))
            <x-slot:pagination>
                {{ $media_items->links('forms::material-admin-26.pagination') }}
            </x-slot:pagination>
        @endif

</x-forms::table>


