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

`@javaabu/cms` isn't on npm: javaabu/manifest's Vite plugin resolves it to the package's `resources/js`. `php artisan manifest:types` adds it to `tsconfig.json`.

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
