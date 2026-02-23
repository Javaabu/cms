@extends('admin.media.media')

@section('page-title', _d('Edit Media'))

@section('content')
    <div class="row">

        <div class="col-md-8">
            @include('cms::admin.media.details._preview')
            @include('cms::admin.media.details._general')
        </div>
        <div class="col-md-4">
            <x-forms::form :model="$media" method="PATCH" :action="translate_route('admin.media.update', $media)" :files="true">

            @include('cms::admin.media.form._language')

            @include('cms::admin.media.form._general')
            @component('admin.components.tags-card', [
                'model' => $media ?? null,
                //'disabled' => disable_field($media ?? null), //wtf is this?
            ])
            @endcomponent

            @include('cms::admin.media.form._submit')
            </x-forms::form>
        </div>
    </div>
@endsection


