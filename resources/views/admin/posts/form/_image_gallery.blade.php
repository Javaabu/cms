@php use Javaabu\Cms\Enums\PostTypeFeatures; @endphp
<x-forms::card :title="$type->getFeatureName(PostTypeFeatures::IMAGE_GALLERY)">
    <div class="form-group">
        @component('cms::admin.components.gallery', [
            'model' => $model,
            'disabled' => $is_translation,
            'input_name' => 'image_gallery',
            'media_type' => 'image',
        ])
        @endcomponent
        @include('errors._list', ['error' => $errors->get('image_gallery')])
    </div>
</x-forms::card>
