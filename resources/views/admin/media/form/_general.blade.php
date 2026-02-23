<div class="card">
    <div class="card-body">
        <div class="form-group">
            <label>{{ __('Url') }} *</label>
            <a href="{{ $media->getUrl() }}" target="_blank" class="form-control-plaintext">
                <i class="zmdi zmdi-open-in-new mr-2"></i> {{ $media->getUrl() }}
            </a>
            @include('errors._list', ['error' => $errors->get('url')])
        </div>

        <x-forms::text name="name" :required="true" :inline="false" />

{{--
        <x-forms::textarea name="description" :required="true" :inline="false" rows="3" class="auto-size lang" />
--}}

    </div>
</div>


