@php
    $name = $name ?? 'title';
@endphp
<th data-sort-field="{{ $name }}" class="{{ add_sort_class($name) }}">{{ _d(Str::title($name)) }}</th>
{{ $before ?? '' }}
@include('admin.components.translations.translations-titles')
<th data-sort-field="published_at" class="{{ add_sort_class('published_at') }}">{{ _d('Date') }}</th>
<th>{{ _d('Status') }}</th>
