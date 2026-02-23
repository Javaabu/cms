@extends('cms::layouts.admin-blank')

@section('content')
    <div class="container-fluid media-picker" data-single="{{ $single ? 'true' : 'false' }}">
        <div class="tab-container">
            @include('cms::admin.media.picker._tabs', ['active' => 'select-media'])

            <div class="tab-content pt-0">
                <div id="select-media" class="{{ add_tab_class(true) }}">
                    @include('cms::admin.media.picker._index')
                </div>

                @can('create', \Javaabu\Cms\Media\Media::class)
                    <div id="new-media" class="{{ add_tab_class(false) }}">
                        @include('cms::admin.media.form._uploader', ['view' => 'grid'])
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection


