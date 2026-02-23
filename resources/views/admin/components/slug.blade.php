<?php
$slug = $slug ?? '';
$url = public_url($url_prefix . '/' . $slug, app()->getLocale());
$name = $name ?? 'slug';
?>
<div class="input-group slug mb-0">
    <div class="input-group-prepend url-prefix d-none">
        <div class="input-group-text">{{ public_url($url_prefix, app()->getLocale()).'/' }}</div>
    </div>
    <a class="url form-control" target="_blank" href="{{ $url }}">{{ $url }}</a>
    <x-forms::hidden :name="$name" :value="old($name)" autocomplete="off" :placeholder="__('enter-:name-here', compact('name'))" />
    <div class="input-group-append">
        <a href="#" class="btn btn-light edit">
            <i class="zmdi zmdi-edit"></i>
        </a>
        <a href="#" title="Cancel" class="hidden btn btn-light cancel">
            <i class="zmdi zmdi-close"></i>
        </a>
    </div>
</div>
