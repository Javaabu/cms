@php
    use \Javaabu\Cms\Enums\PostTypeFeatures;
    use \Javaabu\Cms\Enums\GalleryTypes;
    $type_types = GalleryTypes::getLabels();
    $selected_type = isset($model) ? $model->format : old('format');
@endphp
<x-forms::card>
    <x-forms::select2 name="format" :label="$type->getFeatureName(PostTypeFeatures::FORMAT)" :options="$type_types" :default="$selected_type" allow-clear required />
</x-forms::card>
