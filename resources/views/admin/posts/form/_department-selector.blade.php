@php
    use App\Models\Department;
    $departments_query = Department::userCan('edit', null, 'id');
    $user_has_one_department = $departments_query->count() == 1;
    $user_can_edit_others = auth()->user()->can('edit_others_'. $type->permission_slug);
    $required_star = ! $user_can_edit_others ? ' *' : null;

    if (!$model && $user_has_one_department) {
        $selected_department = $departments_query->first()->id;
    } elseif (!$model && !$user_has_one_department) {
        $selected_department = null;
    } else {
        $selected_department = optional($model->department)->id;
    }

    $departments = $departments_query->pluck('title', 'id')->all();

    $disabled = $user_has_one_department;
    if ($is_translation) {
        $disabled = true;
    }

@endphp
<x-forms::select2 name="department" :options="['' => ''] + $departments" :default="$selected_department" :placeholder="__('No Department Selected')" :allow-clear="$user_can_edit_others"
                  :disabled="$disabled" :required="!$user_can_edit_others" />

@if($user_has_one_department)
    <x-forms::hidden name="department" :default="$selected_department" />
@endif
