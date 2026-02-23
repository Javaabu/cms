<x-forms::filter>
    <div class="row">
        <div class="col-md-3">
            <x-forms::text name="search" :label="__('Search')" :placeholder="__('Search..')" :show-errors="false" :inline="false" />
        </div>

        @if($type->hasFeature(\Javaabu\Cms\Enums\PostTypeFeatures::CATEGORIES) && $type->category_type_id)
            <div class="col-md-3">
                @php
                    $categories = ['' => __('All Categories')] + \Javaabu\Cms\Models\Category::categoryList($type->category_type_id);
                @endphp
                <x-forms::select2
                    name="category"
                    :label="__('Category')"
                    :options="$categories"
                    allow-clear
                    :show-errors="false"
                />
            </div>
        @endif

        <div class="col-md-3">
            <x-forms::select2
                name="status"
                :label="__('Status')"
                :options="['' => __('All Statuses')] + \Javaabu\Cms\Enums\PostStatus::labels()"
                allow-clear
                :show-errors="false"
            />
        </div>

        <div class="col-md-3">
            <x-forms::datetime name="date_from" :show-errors="false" />
        </div>

        <div class="col-md-3">
            <x-forms::datetime name="date_to" :show-errors="false" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            @if(class_exists('\App\Models\Department'))
                @php
                    $departments = ['' => __('All Departments')] + \App\Models\Department::pluck('name', 'id')->toArray();
                @endphp
                <x-forms::select2
                    name="department"
                    :label="__('Department')"
                    :options="$departments"
                    allow-clear
                    :show-errors="false"
                />
            @endif
        </div>

        <div class="col-md-3">
            <x-forms::per-page />
        </div>

        <div class="col-md-3">
            <x-forms::filter-submit :cancel-url="route('admin.posts.index', $type)" />
        </div>
    </div>
</x-forms::filter>
