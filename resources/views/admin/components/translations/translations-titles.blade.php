@php use App\Helpers\Translation\Enums\Languages; @endphp
@foreach(Languages::getKeys() as $language)
    <th class="text-center">
        <img class="flag-thumb" src="{{ Languages::flagUrl($language) }}"
             alt="{{ $language }}">
    </th>
@endforeach
