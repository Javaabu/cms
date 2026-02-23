@extends('admin.media.media')

@section('page-subheading')
    <small>{{ $title }}</small>
@endsection

@section('content')
    @if($media_items->isNotEmpty() || Javaabu\Cms\Media\Media::exists())
        <div class="card">

            <x-forms::form
                :action="translate_route('admin.media.index', app()->getLocale())"
                :model="request()->query()"
                id="filter"
                method="GET"
            >
                @include('cms::admin.media._filter')
            </x-forms::form>

            @if($view == 'list')
                @include('cms::admin.media._table')
            @else
                @include('cms::admin.media._grid')
            @endif
        </div>
    @else
        <x-forms::no-items
            icon="zmdi zmdi-collection-folder-image"
            :create-action="translate_route('admin.media.create')"
            :model_type="_d('media')"
            :model="Javaabu\Cms\Media\Media::class"
        />
    @endif
@endsection


