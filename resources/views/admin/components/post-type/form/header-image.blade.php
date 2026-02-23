<div class="card">
    <div class="card-body">
        <h4 class="card-title">
            {{ _d('Header Image') }}
            <small class="form-text text-muted">{{ $header_image_size ?? _d('Recommended 1920px x 300px') }}</small>
        </h4>

        <div class="form-group mb-0">
            @component('admin.components.attachment-image', [
                'type' => 'image',
                'file_input_id' => 'header_image',
                'model' => isset($lang_parent) ? $lang_parent : ($model ?? null),
                'disabled' => isset($lang_parent),
            ])
            @endcomponent
            @include('errors._list', ['error' => $errors->get('header_image')])
        </div>
    </div>
</div>
