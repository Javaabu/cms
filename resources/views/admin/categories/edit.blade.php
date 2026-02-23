@extends('cms::admin.categories.categories')

@section('page-title', __('Edit :type', ['type' => $type->singular_name]))

@section('content')
    <x-forms::form method="PATCH" :model="$category" :action="route('admin.categories.update', [$type, $category])">
        @include('cms::admin.categories._form')
    </x-forms::form>
@endsection
