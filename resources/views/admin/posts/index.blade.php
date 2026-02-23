@extends('cms::admin.posts.posts')

@section('page-title')
    {{ $trashed ?? false ? __('Deleted :type', ['type' => $type->name]) : $type->name }}
@endsection

@section('page-subheading')
    <small>{{ $title }}</small>
@endsection

@section('content')
    @if($posts->isNotEmpty() || $type->posts()->exists())
        <div class="card">
            <x-forms::form
                :action="route('admin.posts.index', $type)"
                :model="request()->query()"
                id="filter"
                method="GET"
            >
                @include('cms::admin.posts._filter')
            </x-forms::form>

            @include('cms::admin.posts._table')
        </div>
    @else
        <x-forms::no-items
            icon="zmdi zmdi-file-text"
            :create-action="route('admin.posts.create', $type)"
            :model_type="strtolower($type->singular_name)"
            :model="$type"
        />
    @endif
@endsection
