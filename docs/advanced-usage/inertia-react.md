---
title: Inertia React
sidebar_position: 3
---

Set `CMS_VIEWS=inertia` (with `cms.sync_packages` on, every package the CMS uses switches too), install `inertiajs/inertia-laravel`, and resolve pages through `withCmsLayouts()`:

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { registerCmsWidgets, withCmsLayouts } from "@javaabu/cms"
import { resolvePage } from "@javaabu/manifest/inertia"
import { createRoot } from "react-dom/client"

registerCmsWidgets()

createInertiaApp({
  resolve: withCmsLayouts((name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx"))),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

`@javaabu/cms` isn't on npm: javaabu/manifest's Vite plugin resolves it to the package's `resources/js`. `php artisan manifest:types` adds it to `tsconfig.json`. `php artisan manifest:check` lists the npm packages the pages need (`react`, `react-dom`, `@inertiajs/react`, `@javaabu/forms`, `@javaabu/onu`); Blade-only apps aren't asked for them.

## Styles

`ThemeLayout` and `AdminLayout` import the CMS's stylesheet (`resources/css/cms.css`), so Vite bundles it with any page that uses them: nothing to add for the layouts. (manifest's automatic injection only covers Blade pages; it skips Inertia responses.)

The content inside the theme comes from other packages, whose React parts don't load their CSS yet. Import their stylesheets once in your app's CSS:

```css
/* resources/css/app.css */
@import "../../vendor/javaabu/post-types/resources/css/post-types.css";   /* post cards, archives, single posts */
@import "../../vendor/javaabu/widgets/resources/css/widgets.css";         /* widget defaults */
@import "../../vendor/javaabu/block-editor/resources/css/block-editor.css"; /* rendered content */
```

If you replace the layouts with your own, import the CMS's too: `@import "../../vendor/javaabu/cms/resources/css/cms.css";`.

## Shared props

Every page of a CMS route gets:

| Prop | Where | What |
| --- | --- | --- |
| `cms.area` | everywhere | `admin` or `public`: which layout `CmsLayout` picks |
| `cms.site` | everywhere | `Cms::site()`: name, tagline, footer text, home URL, locale, direction, languages |
| `cms.strings` | everywhere | The layouts' strings in the current language |
| `cms.navigation` | admin routes | The user's navigation |
| `menus` | public routes | The `header` and `footer` menus (`menus.header`), as dynamic-menubuilder's `<Menu>` reads them |
| `widgets` | public routes | The `sidebar` and `footer` widgets (`widgets.sidebar`) |

They're lazy: nothing is queried unless the page renders them.

## Exports

```ts
import {
  AdminLayout,        // the admin layout: sidebar navigation and the page
  ThemeLayout,        // the public theme
  CmsLayout,          // AdminLayout or ThemeLayout from cms.area, or nothing
  AdminNavigation,    // the navigation: groups={…}
  LanguageSwitcher,   // locales={…}
  withCmsLayouts,     // wraps a resolver: pages without a layout get CmsLayout (or your layout function)
  useCms,             // the cms shared prop, or null outside a CMS route
  cmsWidgets,         // { RecentPosts, Terms }
  registerCmsWidgets, // registers them with @javaabu/widgets
  RecentPosts,
  Terms,
  Dashboard,          // cms::Admin/Dashboard
  Home,               // cms::Public/Home
  format,             // format("Add :type", { type: "page" })
  type DashboardProps,
  type HomeProps,
  type CmsSharedProps,
  type SiteData,
  type NavigationGroupData,
} from "@javaabu/cms"
```

A page that sets its own `layout` keeps it. To use a different layout for every CMS page, pass it as the second argument:

```tsx
resolve: withCmsLayouts(resolver, (page) => <MyLayout>{page}</MyLayout>)
```
