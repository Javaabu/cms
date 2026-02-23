@component('admin.components.translations.db-language', [
    'model' => $model ?? null,
    'create_url' => $create_url,
    'ajax_url' => $ajax_url,
    'name_field' => 'title',
    'lang_parent' => $lang_parent ?? null,
    'editable_languages' => $editable_languages ?? null,
])
@endcomponent