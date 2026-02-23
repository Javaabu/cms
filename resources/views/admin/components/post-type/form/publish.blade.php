@php
    $can_publish = isset($model) ?
                auth()->user()->can('publish', $model) :
                auth()->user()->can($model_class, 'publish');
@endphp
<x-forms::card :title="__('Publish')">
    {{ $before ?? '' }}

    @unless(isset($hide_publish_date))
        <x-forms::datetime name="published_at" :label="__('Publish Date')" />
    @endunless

    <x-forms::select2 name="status" :options="\Javaabu\Helpers\Enums\PublishStatuses::labels()" :disabled="!$can_publish" :allow-clear="false"/>

    {{--        @if(empty($hide_featured))--}}
    {{--        <div class="form-group">--}}
    {{--            <div class="checkbox">--}}
    {{--                {!! Form::checkbox('featured', 1, old('featured'), ['id' => 'featured-chk']) !!}--}}
    {{--                <label for="featured-chk" class="checkbox__label">{{ _d('Mark as Featured') }}</label>--}}
    {{--            </div>--}}
    {{--            @include('errors._list', ['error' => $errors->get('featured')])--}}
    {{--        </div>--}}
    {{--        @endif--}}

    {{ $after ?? null }}

    <div class="button-group inline-btn-group">
        @if(empty($model) || $model->status != $model->getPublishedKey())
            <button class="btn btn-success btn--icon-text btn--raised" name="action" value="publish">
                <i class="zmdi zmdi-check"></i> {{ $can_publish ? _d('Publish') : _d('Send for review') }}
            </button>
        @endif
        <button class="btn btn-info btn--icon-text btn--raised">
            <i class="zmdi zmdi-floppy"></i> {{ isset($model) ? _d('Update') : _d('Save') }}
        </button>
        @if(empty($model) || ! $model->is_draft)
            <button class="btn btn-light btn--raised btn--icon-text" name="action" value="draft">
                <i class="zmdi zmdi-file-text"></i> {{ _d('Save as draft') }}
            </button>
        @endif
        @if(isset($model) && $model->is_pending && $can_publish)
            <button class="btn btn-danger btn--raised btn--icon-text" name="action" value="reject">
                <i class="zmdi zmdi-close"></i> {{ _d('Reject') }}
            </button>
        @endif
        <a class="btn btn-light btn--icon-text"
           href="{{ $cancel_url ?? translate_route($model_route.'.index', $type) }}">
            <i class="zmdi zmdi-close"></i> {{ _d('Cancel') }}
        </a>
    </div>
</x-forms::card>
