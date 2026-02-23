<x-forms::card>
    <x-forms::card.title>
        {{ __('Featured Image') }}
        <small class="form-text text-muted">{{ $recommendation ?? __('Recommended 1200px x 630px') }}</small>
    </x-forms::card.title>

    <x-forms::form-group name="image" :show-label="false">
        @component('cms::admin.components.attachment-image', [
            'type' => 'image',
            'file_input_id' => $file_input_id ?? 'featured_image',
            'model' => $model ?? null,
            'disabled' => $is_translation ?? false,
        ])
        @endcomponent
        {{--@include('errors._list', ['error' => $errors->get('featured_image')])--}}
    </x-forms::form-group>
</x-forms::card>
