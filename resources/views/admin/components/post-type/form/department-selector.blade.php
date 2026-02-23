<div class="form-group">
    {!! Form::label('department', _d('Department')) !!}
    @php
        $selected_department = isset($model) ? $model->department_id : old('department');
        $departments = \App\Models\Department::whereId($selected_department)->get()->pluck('title', 'id');
    @endphp
    {!! Form::select('department', $departments->prepend('', ''), $selected_department,
    ['class' => add_error_class($errors->has('department')).' select2-ajax',
    'data-select-ajax-url' => api_action([\App\Http\Controllers\Api\DepartmentsController::class, 'index']),
    'data-placeholder' => _d('Nothing Selected'),
    'data-allow-clear' => 'true',
    'required' => !empty($required),
    'data-name-field' => 'title',
    'disabled' => !empty($disabled),
    ]) !!}
    @include('errors._list', ['error' => $errors->get('department')])
</div>
