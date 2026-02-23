@extends('layouts.admin')

@section('title', __('Category Types'))
@section('page-title', __('Category Types'))

@section('top-search')
    @include('admin.partials.search-model', [
        'search_route' => ['admin.category-types.index'],
        'search_placeholder' => __('Search for category types...'),
    ])
@endsection

@section('model-actions')
    @include('cms::admin.category-types._actions')
@endsection
