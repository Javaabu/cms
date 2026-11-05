// The props the Inertia pages and layouts get from PHP (DashboardController,
// HomeController and the ShareCmsData middleware). The same data the Blade views get.

import type { PostTypeData, PublicPost, PublicPostDetails, PublicStrings } from "@javaabu/post-types/types"

/** A language in the switcher (`Translations::switcher()`) */
export interface SiteLocale {
  code: string
  name: string
  native_name: string
  direction: "ltr" | "rtl"
  url: string
  current: boolean
}

/** The site's details (`Cms::site()`) */
export interface SiteData {
  name: string
  tagline: string | null
  footer_text: string | null
  home_url: string
  locale: string
  direction: "ltr" | "rtl"
  locales: SiteLocale[]
}

/** A link in the admin navigation (`Cms::navigationFor()`) */
export interface NavigationItemData {
  key: string
  label: string
  url: string | null
  icon: string | null
  active: boolean
  children: NavigationItemData[]
}

/** A group of links; the first group has no label */
export interface NavigationGroupData {
  key: string
  label: string | null
  items: NavigationItemData[]
}

/** What every page of a CMS route gets as the `cms` shared prop */
export interface CmsSharedProps {
  area: "admin" | "public"
  site: SiteData
  navigation: NavigationGroupData[]
}

/* Dashboard (cms::Admin/Dashboard) */

export interface DashboardPostType {
  key: string
  label: string
  singular_label: string
  icon: string
  counts: { published: number; scheduled: number; draft: number; pending: number }
  index_url: string | null
  create_url: string | null
}

export interface DashboardRecentPost {
  id: number
  title: string
  post_type: string
  post_type_label: string
  status: string
  status_color: string
  updated_at: string | null
  edit_url: string | null
}

export interface DashboardStrings {
  title: string
  content: string
  recent: string
  published: string
  scheduled: string
  draft: string
  pending: string
  /** With :type */
  add: string
  view_all: string
  edit: string
  empty: string
  no_types: string
  /** With :date */
  updated: string
}

export interface DashboardProps {
  post_types: DashboardPostType[]
  recent: DashboardRecentPost[]
  strings: DashboardStrings
}

/* Home page (cms::Public/Home) */

export interface HomeStrings extends Partial<PublicStrings> {
  latest: string
  view_all: string
  welcome: string
  empty: string
}

export interface HomeProps {
  site: SiteData
  /** The front page chosen in the site settings */
  page: PublicPostDetails | null
  post_type: PostTypeData | null
  posts: PublicPost[]
  archive_url: string | null
  strings: HomeStrings
}

/* Widgets */

export interface RecentPostsSettings extends Record<string, unknown> {
  title?: string | null
  post_type?: string
  count?: number
  show_date?: boolean
}

export interface RecentPostsData extends Record<string, unknown> {
  posts: { id: number; title: string; url: string | null; date: string | null; date_label: string | null }[]
  archive_url: string | null
  archive_label?: string
}

export interface TermsSettings extends Record<string, unknown> {
  title?: string | null
  source?: string
  show_counts?: boolean
  hide_empty?: boolean
}

export interface TermsData extends Record<string, unknown> {
  terms: { id: number; name: string; url: string | null; count: number; parent_id: number | null }[]
  show_counts?: boolean
}
