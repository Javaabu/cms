@extends('admin.media.media')

@section('page-title', _d('Add Media'))

@section('content')
    @include('cms::admin.media.form._uploader', ['view' => 'list'])
@endsection


