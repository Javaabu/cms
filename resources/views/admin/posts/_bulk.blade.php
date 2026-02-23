@php
    $actions = [
        'draft' => __('Mark as Draft'),
        'publish' => __('Publish'),
        'markAsPending' => __('Mark as Pending'),
        'delete' => __('Delete'),
    ];
@endphp

<x-forms::bulk-actions :actions="$actions" model="posts" />
