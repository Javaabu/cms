@extends('cms::admin.posts.posts')

@section('page-title', __('New :type', ['type' => $type->singular_name]))

@section('content')
    <x-forms::form :action="route('admin.posts.store', $type)">
        @include('cms::admin.posts._form')
    </x-forms::form>
@endsection
