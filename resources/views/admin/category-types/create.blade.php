@extends('cms::admin.category-types.category-types')

@section('page-title', __('New Category Type'))

@section('content')
    <x-forms::form :action="route('admin.category-types.store')">
        @include('cms::admin.category-types._form')
    </x-forms::form>
@endsection
