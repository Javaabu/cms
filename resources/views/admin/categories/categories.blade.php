@extends('layouts.admin')

@section('title', $type->name)
@section('page-title', $type->name)

@section('top-search')
   {{-- @include('admin.partials.search-model', [
        'search_route' => ['admin.categories.index', $type],
        'search_placeholder' => __('Search for :type...', ['type' => strtolower($type->name)]),
    ])--}}
@endsection

@section('model-actions')
    @include('cms::admin.categories._actions')
@endsection
