@extends('cms::admin.categories.categories')

@section('page-title', __('New :type', ['type' => $type->singular_name]))

@section('content')
    <x-forms::form :action="route('admin.categories.store', $type)">
        @include('cms::admin.categories._form')
    </x-forms::form>
@endsection
