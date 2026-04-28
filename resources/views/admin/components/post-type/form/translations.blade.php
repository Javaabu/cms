@php
    use Javaabu\Cms\Enums\Languages;
    $lang = $model->lang ?? null;
    $langValue = $lang instanceof \BackedEnum ? $lang->value : $lang;
    $is_translation = isset($model) && $langValue && $langValue != app()->getLocale();
@endphp

@if($is_translation)
    <x-forms::text name="original_lang" :label="__('Primary Language')" :default="Languages::getLabelFromKey(optional($model)->lang->value ?? old('lang'))" readonly />

    @isset($model)
        <x-forms::checkbox name="hide_translation" :default="$model->hide_translation" />

        <x-forms::hidden name="language" :default="app()->getLocale()" />

        <div class="form-group">
            <a class="btn btn-light btn--icon-text" href="{{ $model->getAdminLocalizedEditUrl($model->lang->value) }}">
                <i class="zmdi zmdi-file"></i> {{ __('View Original Post') }}
            </a>
        </div>
    @endisset
    <x-forms::hidden name="is_translation" :default="1" />
@else
    <x-forms::select2 name="lang" :label="__('Primary Language')" :default="old('lang', app()->getLocale())" :options="Languages::getLabels()" allow-clear
    :data-switch-locale-route="Languages::translateCurrentRoute(app()->getLocale())" />
    @isset($model)
        <div class="form-group">
            <a class="btn btn-light btn--icon-text" href="{{ $model->translation_url }}">
                @if(! $model->hasTranslations())
                    <i class="zmdi zmdi-plus"></i> {{ __('Add Translation') }}
                @else
                    <i class="zmdi zmdi-edit"></i> {{ __('Edit Translation') }}
                @endif
            </a>
        </div>
    @endisset
@endif

@if(empty($model))
    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#lang').on('change', function () {
                    var _this = $(this);
                    var url = _this.data('switch-locale-route');
                    Swal.fire({
                        title: 'Are you sure you want to switch languages?',
                        text: 'Your inputs will be cleared!',
                        icon: 'warning',
                        confirmButtonText: `Yes`,
                        showCancelButton: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            redirectPage(url);
                        }
                    })
                })
            });
        </script>
    @endpush
@endif

