@php
    $create_url = $create_url ?? '';
    $translatable = $model ?? $create_url;
    $name_field = $name_field ?? 'name';
    $id_field = $id_field ?? 'id';
@endphp

<div class="card">
    <div class="card-body">
        <h4 class="card-title">{{ __('Language') }}</h4>

        <div class="form-group">
            {!! Form::label('language', __('Language of this post')) !!}
            @php
                $selected_language = languages()->current();
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
                $lang_parents = isset($model) && (! languages()->isDefault()) ?
                    [$model->{$id_field} => $model->translate($name_field, languages()->default())] :
                    [];
                $selected_lang_parent = isset($model) ? $model->id : null;
            @endphp
            <div class="input-group mb-0">
                {!! Form::select('lang_parent', ['' => ''] + $lang_parents, $selected_lang_parent, [
                    'class' => 'form-control select2-basic',
                    'data-allow-clear' => 'true',
                    'data-placeholder' => __('Nothing Selected'),
                    'disabled' => true,
                ]) !!}
                @if(
                    isset($model)
                    && (! languages()->isDefault())
                    && (is_null($editable_languages) || in_array(languages()->defaultLanguageCode(), $editable_languages))
                )
                    <div class="input-group-append">
                        <a class="btn btn-light"
                           href="{{ $model->getAdminLocalizedUrl(languages()->default()) }}"
                           title="View Parent"
                           target="_blank">
                            <i class="zmdi zmdi-open-in-new"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if(isset($model))
            <div class="form-group mb-0">
                {!! Form::label('translations', __('Translations'), ['class' => 'd-block mb-4']) !!}
                <div class="list-group">
                    @foreach(languages()->except(app()->getLocale()) as $language)
                        @php
                            $editable = is_null($editable_languages) || in_array($language->code, $editable_languages);
                        @endphp

                        <div class="list-group-item media p-0 mb-2">
                            <div class="float-left">
                                <img class="pt-0 mr-2" src="{{ $language->flag_url }}" alt="{{ $language->code }}">
                            </div>
                            <div class="actions float-right">
                                @if($model->hasTranslations($language->code))
                                    <a class="actions__item zmdi zmdi-edit{{ ! $editable ? ' disabled' : '' }}"
                                       href="{{ $editable ? $model->getAdminLocalizedUrl($language->code) : '' }}"
                                       title="{{ $editable ? __('Edit Translation') : __('Not Allowed to Edit Translation') }}">
                                    </a>
                                @else
                                    <a class="actions__item zmdi zmdi-plus{{ ! $editable ? ' disabled' : '' }}"
                                       href="{{ $editable ? $model->getAdminLocalizedUrl($language->code) : '' }}"
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
