@php
    use App\Helpers\Translation\Enums\Languages;$create_url = $create_url ?? '';
@endphp

@foreach(Languages::getKeys() as $language)
    @php
        $has_translation = $model->hasTranslations($language);
        $translation_hidden = $model->isTranslationHidden($language);
        $is_primary_language = $language == $model->lang;
    @endphp

    <td data-col="{{ $language }}">
        <div class="actions show-always text-center">
            <a class="actions__item zmdi zmdi-{{ $has_translation ? 'edit' : 'plus' }} {{ $is_primary_language ? 'text-accent' : ($translation_hidden ? 'text-washed-out' : '') }}
                {{ auth()->user()->can('update', $model) ? '' : ' disabled' }}
                "
               @can('update', $model)
                   href="{{ $model->getAdminLocalizedEditUrl($language) }}"
               @endcan
               title="{{ $has_translation ? __('Edit Translation') : __('Add Translation') }}"
            >
            </a>
        </div>
    </td>
@endforeach
