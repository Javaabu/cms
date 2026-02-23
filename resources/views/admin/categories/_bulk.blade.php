@php
    $actions = [];

    if (isset($type) && auth()->user()->can('delete', $type)) {
        $actions['delete'] = __('Delete');
    }
@endphp

<x-forms::bulk-actions :actions="$actions" model="categories" />
