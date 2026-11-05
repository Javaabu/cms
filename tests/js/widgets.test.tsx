import { render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { RecentPosts, Terms, cmsWidgets, format, registerCmsWidgets } from "@javaabu/cms"
import { registeredWidgets } from "@javaabu/widgets/registry"

const widget = { id: 1, type: "recent-posts", component: "RecentPosts", location: "sidebar" }

describe("widgets", () => {
  it("renders recent posts with dates and the archive link", () => {
    render(
      <RecentPosts
        widget={{ ...widget, settings: {}, data: { posts: [], archive_url: null } }}
        settings={{ title: "ފަހުގެ ޚަބަރު", show_date: true }}
        data={{
          posts: [
            {
              id: 1,
              title: "ޚަބަރު",
              url: "/news/1",
              date: "2026-11-01T08:00:00+05:00",
              date_label: "1 November 2026",
            },
          ],
          archive_url: "/news",
          archive_label: "All news",
        }}
      />,
    )

    expect(screen.getByRole("heading", { name: "ފަހުގެ ޚަބަރު" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ޚަބަރު" })).toHaveAttribute("href", "/news/1")
    expect(screen.getByText("1 November 2026")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "All news" })).toHaveAttribute("href", "/news")
  })

  it("hides the dates and renders posts without links", () => {
    render(
      <RecentPosts
        widget={{ ...widget, settings: {}, data: { posts: [], archive_url: null } }}
        settings={{ show_date: false }}
        data={{
          posts: [{ id: 1, title: "ޚަބަރު", url: null, date: "2026-11-01", date_label: "1 November" }],
          archive_url: null,
        }}
      />,
    )

    expect(screen.queryByRole("link")).not.toBeInTheDocument()
    expect(screen.queryByText("1 November")).not.toBeInTheDocument()
    expect(screen.queryByRole("heading")).not.toBeInTheDocument()
  })

  it("renders terms with counts", () => {
    render(
      <Terms
        widget={{ ...widget, type: "terms", component: "Terms", settings: {}, data: { terms: [] } }}
        settings={{ title: "ބައިތައް" }}
        data={{
          terms: [
            { id: 1, name: "ރާއްޖެ", url: "/news/categories/local", count: 3, parent_id: null },
            { id: 2, name: "ބޭރު", url: null, count: 0, parent_id: null },
          ],
          show_counts: true,
        }}
      />,
    )

    expect(screen.getByRole("link", { name: "ރާއްޖެ" })).toHaveAttribute("href", "/news/categories/local")
    expect(screen.getByText("(3)")).toBeInTheDocument()
    expect(screen.getByText("ބޭރު").tagName).toBe("SPAN")
  })

  it("registers the widgets with @javaabu/widgets", () => {
    registerCmsWidgets()

    expect(registeredWidgets().RecentPosts).toBe(cmsWidgets.RecentPosts)
    expect(registeredWidgets().Terms).toBe(cmsWidgets.Terms)
  })

  it("formats placeholders", () => {
    expect(format("Add :type", { type: "ޚަބަރު" })).toBe("Add ޚަބަރު")
  })
})
