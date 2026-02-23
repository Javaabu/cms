@php
    use App\Helpers\Translation\Enums\Languages;use Illuminate\Database\Eloquent\Model;$create_url = $create_url ?? '';
    $editable_languages = $editable_languages ?? null; // null means can edit all
@endphp

@foreach(Languages::getKeys() as $language)
    @php
        $translation = $model->getTranslation($language);
        $translation_hidden = $model->isTranslationHidden($language);
        $is_primary_language = $language == $model->lang;
    @endphp

    <td data-col="{{ $language }}">
        <div class="actions show-always text-center">
            @if($translation instanceof Model)
                <a class="actions__item zmdi zmdi-edit {{ ! $translation_hidden ? 'text-accent' : '' }}"
                   href="{{ $translation->admin_url }}"
                   title="{{ __('Edit Translation') }}">
                </a>
            @elseif($translation)
                <a class="actions__item zmdi zmdi-edit {{ ! $translation_hidden ? 'text-accent' : '' }}"
                   href="{{ $model->translation_url }}"
                   title="{{ __('Edit Translation') }}">
                </a>
            @else
                <a class="actions__item zmdi zmdi-plus {{ ! $translation_hidden ? 'text-accent' : '' }}"
                   href="{{ $model->translation_url }}"
                   title="{{ __('Add Translation') }}">
                </a>
            @endif
        </div>
    </td>
@endforeach
