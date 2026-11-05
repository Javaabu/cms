---
title: The public theme
sidebar_position: 4
---

`Route::cms()`'s pages, and post-types' archives, term archives and single posts, render inside the theme:

- a header with the site name (linking home), the tagline, the `header` menu and, with javaabu/translatable, a language switcher;
- the page, with the `sidebar` widgets next to it when there are any;
- a footer with the `footer` widgets, the `footer` menu and the footer text (or "© {year} {site}").

The CMS points post-types' `public_layout` at it, and the admin pages of every package at the admin layout (see `cms.sync_packages` in [Configuration](../advanced-usage/configuration.md)).

## The home page

`/` shows the front page chosen in the site settings, if any, then the latest posts of the home post type (the site setting, else `cms.home.post_type`, default `news`; `cms.home.count`, default 6).

| | Blade | Inertia |
| --- | --- | --- |
| View | `cms.home` in your views, else `cms::public.home` | `cms::Public/Home` |
| Data | `$page` (the front page or null), `$post_type`, `$posts` (post cards), `$archive_url`, `$site`, `$strings`, `$layout`, `$layout_section` | the same props |

## Blade

The layout is `cms::layouts.public`. Pages extend it and fill the `content` section:

```blade
@extends('cms::layouts.public')

@section('title', __('Contact us'))

@section('content')
    <h1 class="cms-heading">{{ __('Contact us') }}</h1>
    …
@endsection
```

Add `@section('full_width', true)` to leave out the sidebar on a page. `@stack('head')` and `@stack('scripts')` are there for your tags.

Use your own layout instead by pointing `cms.layouts.public` at it. It can use the same parts:

```blade
@php($site = \Javaabu\Cms\Facades\Cms::site())

<x-dynamic-menubuilder::menu location="header" class="cms-menu cms-menu--header" />
<x-cms::language-switcher />
<x-widgets::location name="sidebar" class="cms-widgets" />
<x-dynamic-menubuilder::menu location="footer" class="cms-menu cms-menu--footer" />
```

`Cms::site()` returns `name`, `tagline`, `footer_text`, `home_url`, `locale`, `direction` (`rtl` for Dhivehi) and `locales` (for a switcher).

To change one template, publish the views (`php artisan vendor:publish --tag=cms-views`) and edit `resources/views/vendor/cms/...`. For post-types' own templates (a news article, the archive), add `resources/views/post-types/news/single.blade.php` and so on: see post-types' docs.

## React

The theme is `ThemeLayout` from `@javaabu/cms`, the same markup as the Blade layout. `withCmsLayouts()` applies it to every page of a `Route::cms()` route without a layout of its own ([Inertia React](../advanced-usage/inertia-react.md)). To use it on your own page:

```tsx
import { ThemeLayout } from "@javaabu/cms"

export default function Contact() {
  return <h1 className="cms-heading">ފުރަތަމަ ސަފްހާ</h1>
}

Contact.layout = (page: React.ReactNode) => <ThemeLayout>{page}</ThemeLayout>
```

| Prop | Default |
| --- | --- |
| `site` | `cms.site` from the shared props |
| `widgets` | the `widgets` shared prop (`Widgets::props()`) |
| `components` | your widget types' components, over the CMS's and the built-in ones |
| `fullWidth` | `false`; `true` leaves out the sidebar |
| `strings` | `cms.strings`, translated |

The menus come from the `menus` shared prop, which the CMS fills on its public routes.

To change the theme itself, override the file: `php artisan manifest:override cms js/components/ThemeLayout.tsx`, then edit `resources/vendor/cms/js/components/ThemeLayout.tsx`.

## Styles

`resources/css/cms.css` (asset `cms::styles`) styles the layouts with plain CSS and logical properties, so it needs no Tailwind and works right to left. Change the look with its custom properties:

```css
:root {
  --cms-color-primary: #8a1538;
  --cms-container: 80rem;
  --cms-font: "Inter", sans-serif;
  --cms-font-thaana: "MV Faseyha", "Faruma", sans-serif;
}
```
