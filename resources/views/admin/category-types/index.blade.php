@extends('cms::admin.category-types.category-types')

@section('page-title')
    {{ __('Category Types') }}
@endsection

@section('page-subheading')
    <small>{{ $title }}</small>
@endsection

@section('content')
    @if($category_types->isNotEmpty() || \Javaabu\Cms\Models\CategoryType::exists())
        <div class="card">
            <x-forms::form
                :action="route('admin.category-types.index')"
                :model="request()->query()"
                id="filter"
                method="GET"
            >
                @include('cms::admin.category-types._filter')
            </x-forms::form>

            @include('cms::admin.category-types._table')
        </div>
    @else
        <x-forms::no-items
            icon="zmdi zmdi-label"
            :create-action="route('admin.category-types.create')"
            model_type="category type"
            model="category_type"
        />
    @endif
@endsection
