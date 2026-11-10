@php($site = \Javaabu\Cms\Facades\Cms::site())
@php($section = config('cms.layouts.section', 'content'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $site['locale']) }}" dir="{{ $site['direction'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $site['name'] }}</title>
    @manifestRequire('cms::styles')
    @stack('head')
</head>
<body class="cms-admin">
    <a class="cms-skip-link" href="#cms-main">{{ __('Skip to content') }}</a>

    <div class="cms-admin__shell">
        <aside class="cms-admin__sidebar">
            <a class="cms-admin__brand" href="{{ $site['home_url'] }}" dir="auto">{{ $site['name'] }}</a>

            <x-cms::admin-navigation />
        </aside>

        <main id="cms-main" class="cms-admin__main" tabindex="-1">
            @if(session('status') || session('success'))
                <div class="cms-alert cms-alert--success" role="status">{{ session('status') ?? session('success') }}</div>
            @endif

            @yield($section)
        </main>
    </div>

    @stack('scripts')
</body>
</html>
