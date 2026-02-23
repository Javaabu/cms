@if($media_items->isNotEmpty() || Javaabu\Cms\Media\Media::exists())
    <div class="card">
        <x-forms::form
            :action="translate_route('admin.media.picker', app()->getLocale())"
            :model="request()->query()"
            id="filter"
            method="GET"
        >
        @include('cms::admin.media.picker._filter')
        </x-forms::form>
    </div>

    <div class="jscroll">
        @include('cms::admin.media._grid', [
            'no_bulk' => true,
            'no_checkbox' => true,
            'hide_actions' => true,
            'selectable' => true,
        ])
    </div>

    @push('scripts')
        <script type="text/javascript" src="{{ asset('build/public/vendors/jscroll/dist/jquery.jscroll.min.js') }}"></script>
    @endpush
@else
    <div class="no-items py-5">
        <div class="card-body">
            <i class="{{ 'zmdi zmdi-collection-folder-image' }} main-icon mb-4"></i>
            <p class="lead mb-4">
                {{ _d('It\'s a bit lonely here.') }}<br/>
                {{ _d('Upload some new media') }}
            </p>
        </div>
    </div>
@endif


