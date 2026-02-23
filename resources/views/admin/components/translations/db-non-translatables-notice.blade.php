@if(isset($lang_parent) && ($attributes = $lang_parent->getAllNonTranslatables()))
    @php
        $attributes = implode(', ', $attributes);
        $parent_lang = $lang_parent->language ? $lang_parent->language->name : __('root translation');
    @endphp
    @include('admin.components.translations.non-translatables-notice')
@endif
