import type { ComponentType, ReactNode } from "react"
import { CmsLayout } from "./components/CmsLayout"

type PageComponent = ComponentType<Record<string, unknown>> & { layout?: unknown }
type PageModule = { default: PageComponent } | PageComponent

/**
 * Wrap an Inertia page resolver so pages without a layout of their own get
 * the CMS's: the admin layout on Route::cmsAdmin() routes (the CMS's and its
 * packages' pages), the theme on Route::cms() routes, and nothing elsewhere.
 *
 *     resolve: withCmsLayouts((name) => resolvePage(name, import.meta.glob("./pages/**\/*.tsx")))
 */
export function withCmsLayouts<T extends PageModule>(
  resolve: (name: string) => T | Promise<T>,
  layout: (page: ReactNode) => ReactNode = (page) => <CmsLayout>{page}</CmsLayout>,
): (name: string) => Promise<T> {
  return async (name: string) => {
    const module = await resolve(name)
    const page = (typeof module === "function" ? module : module.default) as PageComponent

    if (page && page.layout === undefined) {
      page.layout = layout
    }

    return module
  }
}
