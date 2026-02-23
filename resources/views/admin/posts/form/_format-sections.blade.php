<div data-enable-elem="#format"
     data-enable-section-value="{{ \Javaabu\Cms\Enums\GalleryTypes::PHOTO }}"
     data-hide-fields="true"
>
    <x-forms::card :title="__('Images')">
        <div class="form-group">
            @component('cms::admin.components.gallery', [
                'model' => $model,
                'disabled' => $is_translation,
                'input_name' => 'image_gallery',
                'media_type' => 'image',
            ])
            @endcomponent
            @include('errors._list', [
                'error' => $errors->get($type->getFeatureCollectionName(\Javaabu\Cms\Enums\PostTypeFeatures::FORMAT))
            ])
        </div>
    </x-forms::card>
</div>

<div data-enable-elem="#format"
     data-enable-section-value="{{ \Javaabu\Cms\Enums\GalleryTypes::VIDEO }}"
     data-hide-fields="true"
>
    <x-forms::card :title="__('Video Link')">
        @component('cms::admin.components.post-type.form.text-input', [
            'name' => 'video_url',
            'title' => __('Video Link'),
            'placeholder' => __('Video Link'),
            'disabled' => $is_translation
        ])
        @endcomponent
    </x-forms::card>
</div>
