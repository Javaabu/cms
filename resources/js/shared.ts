import { usePage } from "@inertiajs/react"
import type { CmsSharedProps } from "./types"

/** The `cms` shared prop, or null outside a CMS route (or outside Inertia) */
export function useCms(): CmsSharedProps | null {
  try {
    const props = usePage().props as Record<string, unknown>

    return (props.cms as CmsSharedProps | undefined) ?? null
  } catch {
    return null
  }
}
