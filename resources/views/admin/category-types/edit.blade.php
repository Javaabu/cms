@extends('cms::admin.category-types.category-types')

@section('page-title', __('Edit Category Type'))

@section('content')
    <x-forms::form method="PATCH" :model="$category_type" :action="route('admin.category-types.update', $category_type)">
        @include('cms::admin.category-types._form')
    </x-forms::form>
@endsection
