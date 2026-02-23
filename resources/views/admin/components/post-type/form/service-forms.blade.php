@php
    $name = $name ?? 'forms';
    $title = $title ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
@endphp
<div class="form-group">
    {!! Form::label($name, $title) !!}
    @php
        $selected_forms = isset($model) ? $model->forms->pluck('id') : old($name, []);
        $forms = \App\Models\Form::whereIn('id', $selected_forms)->get()->pluck('title', 'id');
    @endphp
    {!! Form::select($name.'[]', $forms, $selected_forms,
    ['class' => add_error_class($errors->has($name)).' select2-ajax',
    'data-select-ajax-url' => api_action([\App\Http\Controllers\Api\FormsController::class, 'index']),
    'data-placeholder' => _d('Nothing Selected'),
    'data-allow-clear' => 'true',
    'required' => !empty($required),
    'multiple' => true,
    'disabled' => !empty($disabled),
    'data-name-field' => 'title',
    ]) !!}
    @include('errors._list', ['error' => $errors->get($name)])
</div>

{!! Form::hidden('sync_'.$name) !!}
