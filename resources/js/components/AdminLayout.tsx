import type { ReactNode } from "react"
import { useCms } from "../shared"
import type { LayoutStrings, NavigationGroupData, SiteData } from "../types"
import { AdminNavigation } from "./AdminNavigation"

export interface AdminLayoutProps {
  children: ReactNode
  /** Defaults to the `cms` shared prop */
  site?: SiteData
  navigation?: NavigationGroupData[]
  strings?: Partial<LayoutStrings>
}

/** The admin layout: the same markup as cms::layouts.admin */
export function AdminLayout({ children, site, navigation, strings: own = {} }: AdminLayoutProps) {
  const cms = useCms()
  const strings = { ...cms?.strings, ...own }
  const details = site ?? cms?.site
  const groups = navigation ?? cms?.navigation ?? []

  return (
    <div className="cms-admin" dir={details?.direction} lang={details?.locale}>
      <a className="cms-skip-link" href="#cms-main">
        {strings.skip ?? "Skip to content"}
      </a>
      <div className="cms-admin__shell">
        <aside className="cms-admin__sidebar">
          {details ? (
            <a className="cms-admin__brand" href={details.home_url} dir="auto">
              {details.name}
            </a>
          ) : null}
          <AdminNavigation groups={groups} label={strings.navigation} />
        </aside>
        <main id="cms-main" className="cms-admin__main" tabIndex={-1}>
          {children}
        </main>
      </div>
    </div>
  )
}
