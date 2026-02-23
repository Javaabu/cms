@php use Javaabu\Cms\Media\AllowedMimeTypes; @endphp
<x-forms::filter>
    <div class="row">
        <div class="col-md-6">
            <x-forms::text name="search" :label="__('Search')" :placeholder="__('Search..')" :show-errors="false"
                           :inline="false"/>
        </div>
        <div class="col-md-3">
            <x-forms::select2
                name="type"
                :label="_d('Type')"
                :options="AllowedMimeTypes::getTypeLabels()"
                allow-clear
                :show-errors="false"
            />
        </div>
        <div class="col-md-3">
            <x-forms::select2
                name="stock"
                :label="_d('Stock Footage?')"
                :options="['' => '', '1' => _d('Marked as Stock'), '0' => _d('Not Marked as Stock')]"
                allow-clear
                :placeholder="_d('All')"
                :show-errors="false"
            />
        </div>
    </div>

    @component('admin.components.translations.filters', [
                'filter_url' => add_query_arg(compact('view'), translate_route('admin.media.index'))
            ])
    @endcomponent

    <x-forms::hidden name="view" :value="$view"/>
</x-forms::filter>


