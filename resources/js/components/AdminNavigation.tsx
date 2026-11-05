import type { NavigationGroupData, NavigationItemData } from "../types"

export interface AdminNavigationProps {
  groups: NavigationGroupData[]
  /** The <nav>'s accessible name */
  label?: string
  className?: string
}

function Item({ item }: { item: NavigationItemData }) {
  return (
    <li className={`cms-nav__item${item.active ? " cms-nav__item--active" : ""}`} data-key={item.key}>
      {item.url ? (
        <a
          className="cms-nav__link"
          href={item.url}
          aria-current={item.active ? "page" : undefined}
          data-icon={item.icon ?? undefined}
        >
          {item.label}
        </a>
      ) : (
        <span className="cms-nav__link" data-icon={item.icon ?? undefined}>
          {item.label}
        </span>
      )}
      {item.children.length ? (
        <ul className="cms-nav__children">
          {item.children.map((child) => (
            <Item key={child.key} item={child} />
          ))}
        </ul>
      ) : null}
    </li>
  )
}

/** The admin navigation: the same markup as <x-cms::admin-navigation /> */
export function AdminNavigation({ groups, label = "Admin", className }: AdminNavigationProps) {
  return (
    <nav className={["cms-nav", className].filter(Boolean).join(" ")} aria-label={label}>
      {groups.map((group) => (
        <div key={group.key || "main"} className="cms-nav__group">
          {group.label ? (
            <p className="cms-nav__heading" id={`cms-nav-${group.key}`}>
              {group.label}
            </p>
          ) : null}
          <ul className="cms-nav__list" aria-labelledby={group.label ? `cms-nav-${group.key}` : undefined}>
            {group.items.map((item) => (
              <Item key={item.key} item={item} />
            ))}
          </ul>
        </div>
      ))}
    </nav>
  )
}
