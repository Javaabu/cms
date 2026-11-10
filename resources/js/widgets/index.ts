import { registerWidgets } from "@javaabu/widgets/registry"
import type { WidgetComponents } from "@javaabu/widgets/types"
import { RecentPosts } from "./RecentPosts"
import { Terms } from "./Terms"

/** The CMS's widget types, by component name (Widget::component()) */
export const cmsWidgets: WidgetComponents = {
  RecentPosts,
  Terms,
}

/** Register the CMS's widgets with @javaabu/widgets, for <WidgetLocation> outside ThemeLayout */
export function registerCmsWidgets(): void {
  registerWidgets(cmsWidgets)
}

export { RecentPosts, Terms }
