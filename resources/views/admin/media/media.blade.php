@extends('layouts.admin')

@section('title', 'Media Library')
@section('page-title', _d('Media Library'))

@section('top-search')
    @include('cms::admin.partials.search-model', [
        'search_route' => ['admin.media.index', app()->getLocale()],
        'search_placeholder' => _d('Search media library...'),
    ])
@endsection

@section('model-actions')
    @include('cms::admin.media._actions')
@endsection



