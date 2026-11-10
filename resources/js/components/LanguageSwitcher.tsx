import type { SiteLocale } from "../types"

/** Links to the current page in each language; nothing with fewer than two */
export function LanguageSwitcher({ locales, label = "Language" }: { locales: SiteLocale[]; label?: string }) {
  if (locales.length < 2) return null

  return (
    <nav className="cms-languages" aria-label={label}>
      <ul className="cms-languages__list">
        {locales.map((locale) => (
          <li key={locale.code}>
            <a
              href={locale.url}
              lang={locale.code}
              dir={locale.direction}
              hrefLang={locale.code}
              aria-current={locale.current ? "true" : undefined}
            >
              {locale.native_name}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  )
}
