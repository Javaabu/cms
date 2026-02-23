@extends('cms::admin.categories.categories')

@section('page-title')
    {{ $type->name }}
@endsection

@section('page-subheading')
    <small>{{ $title }}</small>
@endsection

@section('content')
    @if($categories->isNotEmpty() || $type->categories()->exists())
        <div class="card">
            <x-forms::form
                :action="route('admin.categories.index', $type)"
                :model="request()->query()"
                id="filter"
                method="GET"
            >
                @include('cms::admin.categories._filter')
            </x-forms::form>

            @include('cms::admin.categories._table')
        </div>
    @else
        <x-forms::no-items
            icon="zmdi zmdi-label-alt"
            :create-action="route('admin.categories.create', $type)"
            :model_type="strtolower($type->singular_name)"
            :model="$type"
        />
    @endif
@endsection
