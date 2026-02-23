@php
    $name = $name ?? 'name';
    $title = $title ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
    $placeholder = $placeholder ?? $title;
    $hide_errors_list = isset($hide_errors_list);

    $attribs = $attribs ?? [];

    $attribs = array_merge([
        'class' => add_error_class($errors->has($name)),
        'placeholder' => $placeholder,
        'min' => 0,
        'max' => 999,
        'step' => 1,
        'required' => !empty($required),
        'disabled' => !empty($disabled),
    ], $attribs);

    $value = $value ?? old($name);

    if (!empty($required)) {
        $title = $title . ' *';
    }

@endphp

<div class="form-group">
    {!! Form::label($name, $title) !!}
    {!! Form::number($name, $value, $attribs) !!}
    @if(! $hide_errors_list)
        @include('errors._list', ['error' => $errors->get($name)])
    @endif
    <i class="form-group__bar"></i>
</div>






