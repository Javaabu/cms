@extends('cms::admin.posts.posts')

@section('page-title', __('Edit :type', ['type' => $type->singular_name]))

@section('content')
    <x-forms::form method="PATCH" :model="$post" :action="route('admin.posts.update', [$type, $post])">
        @include('cms::admin.posts._form')
    </x-forms::form>
@endsection
