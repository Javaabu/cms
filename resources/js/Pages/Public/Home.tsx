import { BlockContent } from "@javaabu/block-editor/react"
import { PostCard } from "@javaabu/post-types/components/PostCard"
import type { PublicStrings } from "@javaabu/post-types/types"
import type { HomeProps } from "../../types"

/** cms::Public/Home — the front page, then the latest posts */
export default function Home({ site, page, post_type, posts, archive_url, strings }: HomeProps) {
  return (
    <div className="cms-home">
      {page ? (
        <article className="cms-home__page" data-post={page.id}>
          <h1 className="cms-heading" dir="auto">
            {page.title}
          </h1>
          {page.content !== null ? (
            <div className="post-types-content">
              <BlockContent html={page.content_html} />
            </div>
          ) : null}
        </article>
      ) : (
        <h1 className="cms-visually-hidden">{site.name}</h1>
      )}

      {post_type ? (
        <section className="cms-home__latest" aria-labelledby="cms-home-latest">
          <header className="cms-home__latest-header">
            <h2 id="cms-home-latest" className="cms-subheading">
              {strings.latest}
            </h2>
            {archive_url ? (
              <a href={archive_url} className="cms-link">
                {strings.view_all}
              </a>
            ) : null}
          </header>

          {posts.length ? (
            <div className="post-types-archive">
              {posts.map((post) => (
                <PostCard key={post.id} post={post} strings={strings as PublicStrings} />
              ))}
            </div>
          ) : (
            <p className="cms-muted">{strings.empty}</p>
          )}
        </section>
      ) : null}
    </div>
  )
}
