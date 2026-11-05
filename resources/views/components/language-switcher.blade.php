@props(['locales' => \Javaabu\Cms\Support\Translations::switcher()])
@if(count($locales) > 1)
    <nav {{ $attributes->merge(['class' => 'cms-languages']) }} aria-label="{{ __('Language') }}">
        <ul class="cms-languages__list">
            @foreach($locales as $locale)
                <li>
                    <a href="{{ $locale['url'] }}" lang="{{ $locale['code'] }}" dir="{{ $locale['direction'] }}" hreflang="{{ $locale['code'] }}" @if($locale['current']) aria-current="true" @endif>{{ $locale['native_name'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
