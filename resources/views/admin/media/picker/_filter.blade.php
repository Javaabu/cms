@php use App\Helpers\Translation\Enums\Languages; @endphp
<x-forms::filter>
    <div class="row">
        <div class="col-md-3">
            <x-forms::text name="search" :label="__('Search')" :placeholder="__('Search..')" :show-errors="false"
                           :inline="false"/>
        </div>

        <div class="col-md-3">
            <x-forms::select2
                name="primary_language"
                :options="Languages::getLabels()"
                allow-clear
                :placeholder="_d('Any')"
                :show-errors="false"
            />
        </div>

        <div class="col-md-3">
            <x-forms::select2
                name="is_translated"
                :label="_d('Is Translated')"
                :options="[1 => 'Yes', 0 => 'No']"
                allow-clear
                :placeholder="_d('Any')"
                :show-errors="false"
            />
        </div>

        <div class="col-md-3">
            <x-forms::per-page/>
        </div>
        <div class="col-md-3">
        </div>
    </div>
    <x-forms::filter-submit
        :cancel-url="add_query_arg(compact('type', 'single'), translate_route('admin.media.picker'))"
        :export="isset($export)"/>

    <x-forms::hidden name="view" :value="$view ?? ''"/>
    <x-forms::hidden name="single" :value="$single ?? ''"/>
</x-forms::filter>


