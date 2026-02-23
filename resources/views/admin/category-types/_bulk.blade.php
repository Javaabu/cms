@php
    $actions = [];

    if (auth()->user()->can('delete', \Javaabu\Cms\Models\CategoryType::class)) {
        $actions['delete'] = __('Delete');
    }
@endphp

<x-forms::bulk-actions :actions="$actions" model="category_types" />
