@foreach(languages()->all() as $language)
    @php
        $editable = is_null($editable_languages) || in_array($language->code, $editable_languages);
    @endphp

    <a class="dropdown-item {{ languages()->isCurrent($language->code) ? 'active' : '' }}
    {{  empty($use_local_name) ? '' : $language->code }}{{ ! $editable ? ' disabled' : '' }}"
       href="{{ $editable ? $language->getAdminLocalizedUrl($translatable, $current_url ?? '') : '' }}">
        <img src="{{ $language->flag_url }}" alt="{{ $language->code }}" class="mr-2">
        <span class="align-middle">{{ empty($use_local_name) ? $language->name : $language->local_name }}</span>
    </a>
@endforeach