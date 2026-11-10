import type { WidgetComponentProps } from "@javaabu/widgets/types"
import type { TermsData, TermsSettings } from "../types"

/** The terms widget (categories, tags): the same markup as cms::widgets.terms */
export function Terms({ settings, data }: WidgetComponentProps<TermsSettings, TermsData>) {
  const terms = data.terms ?? []

  return (
    <>
      {settings.title ? <h2 className="widget-title">{settings.title}</h2> : null}
      {terms.length ? (
        <ul className="cms-widget-list">
          {terms.map((term) => (
            <li key={term.id}>
              {term.url ? (
                <a href={term.url} dir="auto">
                  {term.name}
                </a>
              ) : (
                <span dir="auto">{term.name}</span>
              )}
              {data.show_counts ? <span className="cms-widget-count">({term.count})</span> : null}
            </li>
          ))}
        </ul>
      ) : null}
    </>
  )
}
