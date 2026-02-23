<div class="card">
    <div class="card-body">
        <h4 class="card-title">
            {{ $type->getFeatureName(\App\Helpers\Enums\PostTypeFeatures::DOCUMENTS) }}
        </h4>

        <div class="form-group mb-0">
            @component('admin.components.gallery', [
                'model' => $model,
                'disabled' => $is_translation,
                'input_name' => 'documents',
                'media_type' => 'document',
            ])
            @endcomponent
            @include('errors._list', ['error' => $errors->get('documents')])
        </div>
    </div>
</div>
