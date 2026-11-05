import { LinkButton, Status } from "@javaabu/forms"
import { format } from "../../format"
import type { DashboardProps } from "../../types"

const STATUSES = ["published", "scheduled", "draft", "pending"] as const

/** cms::Admin/Dashboard — counts per post type and recently edited posts */
export default function Dashboard({ post_types, recent, strings }: DashboardProps) {
  const number = new Intl.NumberFormat()

  return (
    <div className="cms-dashboard">
      <h1 className="cms-heading">{strings.title}</h1>

      <section className="cms-dashboard__section" aria-labelledby="cms-dashboard-content">
        <h2 id="cms-dashboard-content" className="cms-subheading">
          {strings.content}
        </h2>

        {post_types.length ? (
          <div className="cms-dashboard__cards">
            {post_types.map((type) => (
              <article key={type.key} className="cms-card" data-post-type={type.key}>
                <header className="cms-card__header">
                  <h3 className="cms-card__title" data-icon={type.icon}>
                    {type.label}
                  </h3>
                  {type.create_url ? (
                    <LinkButton url={type.create_url} size="sm" startIcon="plus">
                      {format(strings.add, { type: type.singular_label.toLocaleLowerCase() })}
                    </LinkButton>
                  ) : null}
                </header>
                <dl className="cms-card__counts">
                  {STATUSES.map((status) => (
                    <div key={status} className="cms-card__count" data-status={status}>
                      <dt>{strings[status]}</dt>
                      <dd>{number.format(type.counts[status])}</dd>
                    </div>
                  ))}
                </dl>
                {type.index_url ? (
                  <a className="cms-card__link" href={type.index_url}>
                    {strings.view_all}
                  </a>
                ) : null}
              </article>
            ))}
          </div>
        ) : (
          <p className="cms-muted">{strings.no_types}</p>
        )}
      </section>

      {post_types.length ? (
        <section className="cms-dashboard__section" aria-labelledby="cms-dashboard-recent">
          <h2 id="cms-dashboard-recent" className="cms-subheading">
            {strings.recent}
          </h2>

          {recent.length ? (
            <ul className="cms-recent">
              {recent.map((post) => (
                <li key={post.id} className="cms-recent__item" data-post={post.id}>
                  {post.edit_url ? (
                    <a className="cms-recent__title" href={post.edit_url} dir="auto">
                      {post.title}
                    </a>
                  ) : (
                    <span className="cms-recent__title" dir="auto">
                      {post.title}
                    </span>
                  )}
                  <span className="cms-recent__meta">
                    {post.post_type_label} · <Status color={post.status_color} label={post.status} />
                    {post.updated_at ? (
                      <>
                        {" · "}
                        <time dateTime={post.updated_at}>
                          {format(strings.updated, { date: new Date(post.updated_at).toLocaleDateString() })}
                        </time>
                      </>
                    ) : null}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <p className="cms-muted">{strings.empty}</p>
          )}
        </section>
      ) : null}
    </div>
  )
}
