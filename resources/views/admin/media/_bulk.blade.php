<?php
$actions = [];

if (auth()->user()->can('delete_media')) {
    $actions['delete'] = _d('Delete');
}
?>

<x-forms::bulk-actions :actions="$actions" model="media" />

<x-forms::hidden name="view" :value="$view" />


