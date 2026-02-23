<div class="form-group">
    {!! Form::label('related_galleries', _d('Related Galleries')) !!}
    @php
        $selected_galleries = isset($model) ? $model->relatedGalleries->pluck('id') : old('related_galleries', []);
        $galleries = \App\Models\Gallery::whereIn('id', $selected_galleries)->get();
        $galleries_for_select = (clone $galleries)->pluck('title', 'id');
    @endphp
    {!! Form::select('related_galleries[]', $galleries_for_select, $selected_galleries,
    ['class' => add_error_class($errors->has('related_galleries')).' select2-ajax-thumb',
    'data-select-ajax-url' => api_action([\App\Http\Controllers\Api\GalleriesController::class, 'index']),
    'data-placeholder' => _d('Nothing Selected'),
    'data-allow-clear' => 'true',
    'required' => !empty($required),
    'multiple' => true,
    'disabled' => !empty($disabled),
    'data-name-field' => 'title',
    ], (clone $galleries)->mapWithKeys(function ($gallery) {
        return [$gallery->id => ['data-image' => $gallery->square_thumb ?? '', 'data-text' => $gallery->title ?? '']];
    })->all()) !!}
    @include('errors._list', ['error' => $errors->get('related_galleries')])
</div>

@if($model)
    {!! Form::hidden('sync_related_galleries', 1)!!}
@endif
