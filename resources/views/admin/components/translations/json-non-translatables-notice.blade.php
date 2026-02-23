@if(isset($model) && (! languages()->isDefault()))
    @php
        $attributes = implode(', ', $model->getAllNonTranslatables());
        $parent_lang = optional(languages()->default())->name ?: __('root translation');
    @endphp
    @include('admin.components.translations.non-translatables-notice')
@endif
