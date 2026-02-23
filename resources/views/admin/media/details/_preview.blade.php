@php $type = $media->type_slug; @endphp
@if($type == 'image')
    <div class="card">
        <img src="{{ $media->getUrl() }}" class="img-fluid m-center" alt="">
    </div>
@elseif($type == 'video')
    <div class="card">
        <video style="height: 100%; width: 100%" {{--poster="{{ $media->getUrl('preview') }}"--}}
        controls="controls" preload="none">
            <source type="video/mp4" src="{{ $media->getUrl() }}"/>
        </video>
    </div>

    @push('vendor-styles')
        <link href="{{ asset('material-admin/vendors/bower_components/mediaelement/build/mediaelementplayer.css') }}"
              rel="stylesheet">
    @endpush

    @push('vendor-scripts')
        <script
            src="{{ asset('material-admin/vendors/bower_components/mediaelement/build/mediaelement-and-player.min.js') }}"></script>
    @endpush
@else
    <div class="card square">
        <div class="square-content">
            <i class="{{ $media->icon }} media-icon"></i>
        </div>
    </div>
@endif


