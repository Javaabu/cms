@php
    $name = $name ?? 'name';
    $title = $title ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
    $placeholder = $placeholder ?? $title;
    $hide_errors_list = isset($hide_errors_list);

    $attribs = $attribs ?? [];

    $attribs = array_merge([
        'class' => add_error_class($errors->has($name)),
        'placeholder' => $placeholder,
        'required' => !empty($required),
        'disabled' => !empty($disabled),
    ], $attribs);

    $value = $value ?? old($name);

    if (!empty($required)) {
        $title = $title . ' *';
    }
@endphp
<x-forms::text :name="$name" :label="$title" :default="$value" :placeholder="$placeholder" :required="!empty($required)" :disabled="!empty($disabled)" :show-errors="!$hide_errors_list" />
