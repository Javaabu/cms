// The theme's styles: Vite bundles them with the page (manifest skips Inertia responses)
import "../../css/cms.css"
import { Menu } from "@javaabu/dynamic-menubuilder/components/Menu"
import { WidgetLocation } from "@javaabu/widgets/components/WidgetLocation"
import type { WidgetComponents, WidgetLocations } from "@javaabu/widgets/types"
import { usePage } from "@inertiajs/react"
import type { ReactNode } from "react"
import { useCms } from "../shared"
import type { LayoutStrings, SiteData } from "../types"
import { cmsWidgets } from "../widgets"
import { LanguageSwitcher } from "./LanguageSwitcher"

export interface ThemeLayoutProps {
  children: ReactNode
  /** Defaults to the `cms` shared prop */
  site?: SiteData
  /** Defaults to the `widgets` shared prop */
  widgets?: WidgetLocations
  /** Components for your own widget types, over the CMS's and the built-in ones */
  components?: WidgetComponents
  /** Leave out the sidebar on this page */
  fullWidth?: boolean
  strings?: Partial<LayoutStrings>
}

function useSharedWidgets(): WidgetLocations {
  try {
    return ((usePage().props as Record<string, unknown>).widgets ?? {}) as WidgetLocations
  } catch {
    return {}
  }
}

/**
 * The public theme: header with the site name, header menu and language
 * switcher, the page, the sidebar widgets, and the footer widgets, footer
 * menu and footer text. The same markup as cms::layouts.public.
 */
export function ThemeLayout({
  children,
  site,
  widgets,
  components,
  fullWidth = false,
  strings: own = {},
}: ThemeLayoutProps) {
  const cms = useCms()
  const strings = { ...cms?.strings, ...own }
  const shared = useSharedWidgets()
  const details = site ?? cms?.site
  const locations = widgets ?? shared
  const widgetComponents = { ...cmsWidgets, ...components }
  const sidebar = !fullWidth && (locations.sidebar?.length ?? 0) > 0

  return (
    <div className="cms-theme" dir={details?.direction} lang={details?.locale}>
      <a className="cms-skip-link" href="#cms-main">
        {strings.skip ?? "Skip to content"}
      </a>

      <header className="cms-theme__header">
        <div className="cms-theme__container cms-theme__header-inner">
          {details ? (
            <div className="cms-theme__brand">
              <a href={details.home_url} className="cms-theme__site-name" dir="auto">
                {details.name}
              </a>
              {details.tagline ? (
                <p className="cms-theme__tagline" dir="auto">
                  {details.tagline}
                </p>
              ) : null}
            </div>
          ) : null}

          <Menu location="header" label={strings.main ?? "Main"} className="cms-menu cms-menu--header cms-theme__nav" />

          <LanguageSwitcher locales={details?.locales ?? []} label={strings.language} />
        </div>
      </header>

      <div className={`cms-theme__container cms-theme__body${sidebar ? " cms-theme__body--sidebar" : ""}`}>
        <main id="cms-main" className="cms-theme__main" tabIndex={-1}>
          {children}
        </main>

        {sidebar ? (
          <aside className="cms-theme__sidebar" aria-label={strings.sidebar ?? "Sidebar"}>
            <WidgetLocation
              name="sidebar"
              widgets={locations.sidebar}
              components={widgetComponents}
              className="cms-widgets"
            />
          </aside>
        ) : null}
      </div>

      <footer className="cms-theme__footer">
        <div className="cms-theme__container">
          <WidgetLocation
            name="footer"
            widgets={locations.footer}
            components={widgetComponents}
            className="cms-widgets cms-widgets--footer"
          />
          <Menu location="footer" label={strings.footer ?? "Footer"} className="cms-menu cms-menu--footer" />
          <p className="cms-theme__footer-text" dir="auto">
            {details?.footer_text ?? strings.copyright ?? `© ${new Date().getFullYear()} ${details?.name ?? ""}`.trim()}
          </p>
        </div>
      </footer>
    </div>
  )
}
