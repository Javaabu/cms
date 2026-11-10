import type { WidgetComponentProps } from "@javaabu/widgets/types"
import type { RecentPostsData, RecentPostsSettings } from "../types"

/** The recent-posts widget: the same markup as cms::widgets.recent-posts */
export function RecentPosts({ settings, data }: WidgetComponentProps<RecentPostsSettings, RecentPostsData>) {
  const posts = data.posts ?? []
  const showDate = settings.show_date ?? true

  return (
    <>
      {settings.title ? <h2 className="widget-title">{settings.title}</h2> : null}
      {posts.length ? (
        <>
          <ul className="cms-widget-list">
            {posts.map((post) => (
              <li key={post.id}>
                {post.url ? (
                  <a href={post.url} dir="auto">
                    {post.title}
                  </a>
                ) : (
                  <span dir="auto">{post.title}</span>
                )}
                {showDate && post.date ? <time dateTime={post.date}>{post.date_label}</time> : null}
              </li>
            ))}
          </ul>
          {data.archive_url ? (
            <a className="cms-widget-more" href={data.archive_url}>
              {data.archive_label}
            </a>
          ) : null}
        </>
      ) : null}
    </>
  )
}
