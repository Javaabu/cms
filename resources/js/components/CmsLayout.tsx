import type { ReactNode } from "react"
import { useCms } from "../shared"
import { AdminLayout } from "./AdminLayout"
import { ThemeLayout } from "./ThemeLayout"

/**
 * The admin layout on admin routes, the theme on public routes, and nothing
 * on other pages: chosen from the `cms` shared prop
 */
export function CmsLayout({ children }: { children: ReactNode }) {
  const cms = useCms()

  if (cms?.area === "admin") return <AdminLayout>{children}</AdminLayout>
  if (cms?.area === "public") return <ThemeLayout>{children}</ThemeLayout>

  return <>{children}</>
}
