<x-forms::card>
    <x-forms::card.title>
        {{ $type->getFeatureName(Javaabu\Cms\Enums\PostTypeFeatures::DOCUMENTS) }}
        @if($is_translation)
            <br>
            <small class="text-muted">{{ __('Upload translated versions of your documents here') }}</small>
        @endif
    </x-forms::card.title>

    <x-forms::form-group name="documents">
        @component('cms::admin.components.document-gallery', [
            'model' => $model,
            'input_name' => 'documents',
            'collection' => Javaabu\Cms\Enums\PostTypeFeatures::DOCUMENTS->getCollectionName($is_translation),
            'media_type' => 'document',
        ])
        @endcomponent
        @include('errors._list', ['error' => $errors->get('documents')])
    </x-forms::form-group>
</x-forms::card>
