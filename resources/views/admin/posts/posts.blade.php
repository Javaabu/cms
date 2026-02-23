@extends('layouts.admin')

@section('title', $type->name)
@section('page-title', $type->name)

@section('top-search')
{{--    @include('admin.partials.search-model', [
        'search_route' => ['admin.posts.index', $type],
        'search_placeholder' => __('Search for :type...', ['type' => strtolower($type->name)]),
    ])--}}
@endsection

@section('model-actions')
    @include('cms::admin.posts._actions')
@endsection

@push('scripts')
    @vite([
    'vendor/javaabu/cms/resources/js/select2-custom.js',
    'vendor/javaabu/cms/resources/js/editor.js',
    'vendor/javaabu/cms/resources/js/dv.js',
    'vendor/javaabu/cms/resources/js/jquery-menu-editor.js',
    'vendor/javaabu/cms/resources/js/select2-thaana.full.js',
    'vendor/javaabu/cms/resources/js/utilities.js',
    ])
@endpush
