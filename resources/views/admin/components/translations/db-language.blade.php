@php
    $create_url = $create_url ?? '';
    $translatable = $model ?? $create_url;
    $name_field = $name_field ?? 'name';
    $id_field = $id_field ?? 'id';
    $ajax_url = $ajax_url ?? '';
@endphp

<div class="card">
    <div class="card-body">
        <h4 class="card-title">{{ __('Language') }}</h4>

        <div class="form-group">
            {!! Form::label('language', __('Language of this post')) !!}
            @php
                $selected_language = isset($model) ? $model->language : languages()->current();
                $disable_dropdown = isset($model);
            @endphp
            <div class="dropdown">
                <button class="btn btn-block text-left btn-light {{ $disable_dropdown ? ' disabled' : '' }}"
                        data-toggle="dropdown">
                    <img src="{{ $selected_language->flag_url }}" alt="{{ $selected_language->code }}" class="mr-2">
                    <span class="align-middle">{{ $selected_language->name }}</span>
                    @unless($disable_dropdown)
                        <i class="float-right zmdi zmdi-chevron-down mt-1"></i>
                    @endunless
                </button>

                @unless($disable_dropdown)
                    <div class="dropdown-menu">
                        @include('admin.components.translations.language-dropdown-items')
                    </div>
                @endunless
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('lang_parent', __('This is a translation of')) !!}
            @php
                $lang_parents = isset($lang_parent) ? [$lang_parent->{$id_field} => $lang_parent->{$name_field}] : [];
                $selected_lang_parent = isset($lang_parent) ? $lang_parent->id : null;
            @endphp
            <div class="input-group mb-0">
                {!! Form::select('lang_parent', ['' => ''] + $lang_parents, $selected_lang_parent, [
                    'class' => add_error_class($errors->has('lang_parent')).' select2-ajax',
                    'data-select-ajax-url' => $ajax_url,
                    'data-name-field' => $name_field,
                    'data-id-field' => $id_field,
                    'data-allow-clear' => 'true',
                    'data-placeholder' => __('Nothing Selected'),
                    'disabled' => ! (empty($model) || $model->can_update_lang_parent),
                ]) !!}
                @if(isset($lang_parent) && auth()->user()->can('update', $lang_parent))
                    <div class="input-group-append">
                        <a class="btn btn-light"
                           href="{{ $lang_parent->admin_url }}"
                           title="View Parent"
                           target="_blank">
                            <i class="zmdi zmdi-open-in-new"></i>
                        </a>
                    </div>
                @endif
            </div>
            @include('errors._list', ['error' => $errors->get('lang_parent')])
        </div>

        @if(isset($model))
            @php
                $parent_id = $model->isRootTranslation() ? $model->id : $model->lang_parent_id;
            @endphp
            <div class="form-group">
                {!! Form::label('translations', __('Translations'), ['class' => 'd-block mb-4']) !!}
                <div class="list-group">
                    @foreach(languages()->except($model->lang) as $language)
                        @php
                            $editable = is_null($editable_languages) || in_array($language->code, $editable_languages);
                            $translation = $model->getTranslation($language->code);
                        @endphp

                        <div class="list-group-item media p-0 mb-2">
                            <div class="float-left">
                                <img class="pt-0 mr-2" src="{{ $language->flag_url }}" alt="{{ $language->code }}">
                            </div>
                            <div class="actions float-right">
                                @if($translation)
                                    <a class="actions__item zmdi zmdi-edit{{ ! $editable ? ' disabled' : '' }}"
                                       href="{{ $editable ? $translation->admin_url : '' }}"
                                       title="{{ $editable ? __('Edit Translation') : __('Not Allowed to Edit Translation') }}">
                                    </a>
                                @else
                                    <a class="actions__item zmdi zmdi-plus{{ ! $editable ? ' disabled' : '' }}"
                                       href="{{ $editable ?add_query_arg('lang_parent', $parent_id, $language->getAdminLocalizedUrl($create_url)) : '' }}"
                                       title="{{ $editable ? __('Add Translation') : __('Not Allowed to Add Translation') }}">
                                    </a>
                                @endif
                            </div>
                            <div class="media-body">
                                <div class="lgi-heading">{{ $language->name }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
