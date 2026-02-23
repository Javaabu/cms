@php
    $actions = [];

    if (auth()->user()->canDo($model_class, 'publish')) {
        $actions['publish'] = _d('Publish');
        $actions['reject'] = _d('Reject');
    }

    if (auth()->user()->canDo($model_class, 'delete')) {
        $actions['delete'] = _d('Delete');
    }
@endphp

@include('admin.components.bulk', ['actions' => $actions, 'model' => $model_plural_morph])
