@php
    use Illuminate\Support\Facades\Input;
@endphp
@component('admin.components.filter')
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('search', _d('Search')) !!}
                {!! Form::text('search', isset($search) ? $search : old('search'),
                ['class' => 'form-control lang', 'placeholder' => _d('Search..')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('status', _d('Status')) !!}
                @php
                    $selected_status = Input::get('status', old('status'));
                    $statuses = \Javaabu\Helpers\Enums\PublishStatuses::getLabels();
                @endphp
                {!! Form::select('status', ['' => ''] + $statuses, $selected_status, [
                    'class' => 'form-control select2-basic',
                    'data-allow-clear' => 'true',
                    'data-placeholder' => _d('Any')
                ]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category', _d('Category')) !!}
                @php
                    $selected_category = Input::get('category', old('category'));
                    $categories = $category_type->categories()
                                                ->whereId($selected_category)
                                                ->get()
                                                ->pluck('name', 'id');
                @endphp
                {!! Form::select('category', $categories->prepend('', ''), $selected_category, [
                    'class' => 'form-control select2-ajax',
                    'data-select-ajax-url' => add_query_arg('lang', $language->code, action('Api\\CategoriesController@index', $category_type)),
                    'data-allow-clear' => 'true',
                    'data-placeholder' => _d('Any')
                ]) !!}
            </div>
        </div>
        <div class="col-md-3">
            @include('admin.components.per-page')
        </div>
    </div>
    @component('admin.components.filter-submit', compact('filter_url'))
    @endcomponent
@endcomponent
