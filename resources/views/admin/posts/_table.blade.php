<x-forms::table
    model="posts"
    :no-bulk="! empty($no_bulk)"
    :no-checkbox="! empty($no_checkbox)"
    :no-pagination="! empty($no_pagination)"
>

    @if(empty($no_bulk))
    <x-slot:bulk-form :action="route('admin.posts.bulk', $type)">
        @include('cms::admin.posts._bulk')
    </x-slot:bulk-form>
    @endif

    <x-slot:headers>
        <x-forms::table.heading :label="__('Title')" sortable="title" />
        <x-forms::table.heading :label="__('Status')" sortable="status" />
        @if($type->hasFeature(\Javaabu\Cms\Enums\PostTypeFeatures::CATEGORIES) && $type->category_type_id)
            <x-forms::table.heading :label="__('Categories')" />
        @endif
        <x-forms::table.heading :label="__('Published At')" sortable="published_at" />
        <x-forms::table.heading :label="__('Created At')" sortable="created_at" />
    </x-slot:headers>

    <x-slot:rows>
        @if($posts->isEmpty())
            <x-forms::table.empty-row columns="6" :no-checkbox="! empty($no_checkbox)">
                {{ __('No matching posts found.') }}
            </x-forms::table.empty-row>
        @else
            @include('cms::admin.posts._list')
        @endif
    </x-slot:rows>

    @if(empty($no_pagination))
    <x-slot:pagination>
        {{ $posts->links('forms::material-admin-26.pagination') }}
    </x-slot:pagination>
    @endif

</x-forms::table>
