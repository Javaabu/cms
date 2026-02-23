<div class="row">
    <div class="col-md-3">
        <x-forms::select2
            name="primary_language"
            :label="_d('Primary Language')"
            :options="\App\Helpers\Translation\Enums\Languages::getLabels()"
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
        <x-forms::per-page />
    </div>
    <div class="col-md-3">
        <x-forms::filter-submit :cancel-url="$filter_url" :export="isset($export)" />
    </div>
</div>
