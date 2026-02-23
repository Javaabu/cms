@php
    $type_types = \App\Helpers\Enums\PageStyles::labels();
    $selected_type = isset($model) ? $model->page_style : old('page_style');
@endphp
<x-forms::select2 name="page_style" :options="$type_types" :default="$selected_type" allow-clear required />

<div data-enable-elem="#page_style"
     data-enable-section-value="{{ \App\Helpers\Enums\PageStyles::SIDEBAR }}"
     data-hide-fields="true"
>
    @php
        $selected_menu = isset($model) ? $model->sidebar_menu_id : old('sidebar_menu');
        $menus = \App\Models\Menu::get()
                        ->pluck('name', 'id');
    @endphp
    <x-forms::select2 name="sidebar_menu" :default="$selected_menu" :options="$menus->prepend('', '')" allow-clear="" :placeholder="__('Select a sidebar menu')"/>
</div>


