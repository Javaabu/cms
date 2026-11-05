@php($site = \Javaabu\Cms\Facades\Cms::site())
@php($section = config('cms.layouts.section', 'content'))
@php($menus = \Javaabu\DynamicMenubuilder\Facades\Menus::class)
@php($widgets = \Javaabu\Widgets\Facades\Widgets::class)
@php($has_sidebar = $widgets::hasLocation('sidebar') && $widgets::has('sidebar') && ! View::hasSection('full_width'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $site['locale']) }}" dir="{{ $site['direction'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $site['name'] }}</title>
    @if($site['tagline'])
        <meta name="description" content="{{ $site['tagline'] }}">
    @endif
    @manifestRequire('cms::styles')
    @stack('head')
</head>
<body class="cms-theme">
    <a class="cms-skip-link" href="#cms-main">{{ __('Skip to content') }}</a>

    <header class="cms-theme__header">
        <div class="cms-theme__container cms-theme__header-inner">
            <div class="cms-theme__brand">
                <a href="{{ $site['home_url'] }}" class="cms-theme__site-name" dir="auto">{{ $site['name'] }}</a>
                @if($site['tagline'])
                    <p class="cms-theme__tagline" dir="auto">{{ $site['tagline'] }}</p>
                @endif
            </div>

            @if($menus::hasLocation('header') && $menus::items('header'))
                <x-dynamic-menubuilder::menu location="header" :label="__('Main')" class="cms-menu cms-menu--header cms-theme__nav" />
            @endif

            <x-cms::language-switcher :locales="$site['locales']" />
        </div>
    </header>

    <div class="cms-theme__container cms-theme__body{{ $has_sidebar ? ' cms-theme__body--sidebar' : '' }}">
        <main id="cms-main" class="cms-theme__main" tabindex="-1">
            @yield($section)
        </main>

        @if($has_sidebar)
            <aside class="cms-theme__sidebar" aria-label="{{ __('Sidebar') }}">
                <x-widgets::location name="sidebar" class="cms-widgets" />
            </aside>
        @endif
    </div>

    <footer class="cms-theme__footer">
        <div class="cms-theme__container">
            @if($widgets::hasLocation('footer') && $widgets::has('footer'))
                <x-widgets::location name="footer" class="cms-widgets cms-widgets--footer" />
            @endif

            @if($menus::hasLocation('footer') && $menus::items('footer'))
                <x-dynamic-menubuilder::menu location="footer" :label="__('Footer')" class="cms-menu cms-menu--footer" />
            @endif

            <p class="cms-theme__footer-text" dir="auto">
                {{ $site['footer_text'] ?? __('© :year :site', ['year' => now()->year, 'site' => $site['name']]) }}
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
