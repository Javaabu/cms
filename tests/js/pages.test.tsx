import { render, screen, within } from "@testing-library/react"
import type * as React from "react"
import { beforeEach, describe, expect, it, vi } from "vitest"
import {
  AdminLayout,
  CmsLayout,
  Dashboard,
  Home,
  ThemeLayout,
  withCmsLayouts,
  type DashboardProps,
  type HomeProps,
} from "@javaabu/cms"
import dashboard from "./fixtures/dashboard.json"
import homePage from "./fixtures/home-page.json"
import home from "./fixtures/home.json"

let pageProps: Record<string, unknown> = {}

vi.mock("@inertiajs/react", () => ({
  usePage: () => ({ props: pageProps }),
  Link: ({ href, children, ...props }: React.ComponentProps<"a">) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}))

beforeEach(() => {
  pageProps = {}
})

describe("cms::Admin/Dashboard", () => {
  const props = dashboard as unknown as DashboardProps

  it("shows counts per post type with links", () => {
    render(<Dashboard {...props} />)

    expect(screen.getByRole("heading", { level: 1, name: "Dashboard" })).toBeInTheDocument()

    const card = screen.getByRole("heading", { name: "News" }).closest("article")!

    expect(within(card).getByText("Drafts").nextElementSibling).toHaveTextContent("1")
    expect(within(card).getByText("Published").nextElementSibling).toHaveTextContent("1")
    expect(within(card).getByRole("link", { name: /Add news article/ })).toHaveAttribute(
      "href",
      props.post_types[0]!.create_url!,
    )
    expect(within(card).getByRole("link", { name: "View all" })).toHaveAttribute(
      "href",
      props.post_types[0]!.index_url!,
    )
  })

  it("lists the recently edited posts", () => {
    render(<Dashboard {...props} />)

    expect(screen.getByRole("link", { name: "ޑްރާފްޓް" })).toHaveAttribute("href", props.recent[0]!.edit_url!)
    expect(screen.getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" })).toBeInTheDocument()
  })

  it("says so when the user can't see any content", () => {
    render(<Dashboard {...props} post_types={[]} recent={[]} />)

    expect(screen.getByText(props.strings.no_types)).toBeInTheDocument()
    expect(screen.queryByRole("heading", { name: props.strings.recent })).not.toBeInTheDocument()
  })

  it("renders inside the admin layout with the navigation", () => {
    pageProps = dashboard

    render(
      <CmsLayout>
        <Dashboard {...props} />
      </CmsLayout>,
    )

    const nav = screen.getByRole("navigation", { name: "Admin" })

    expect(within(nav).getByRole("link", { name: "Dashboard" })).toHaveAttribute("aria-current", "page")
    expect(within(nav).getByRole("link", { name: "News" })).toHaveAttribute("data-icon", "newspaper")
    expect(within(nav).getByText("Content")).toHaveClass("cms-nav__heading")
    expect(screen.getByRole("link", { name: "Javaabu" })).toHaveAttribute("href", "http://localhost")
  })
})

describe("cms::Public/Home", () => {
  it("lists the latest posts", () => {
    const props = home as unknown as HomeProps

    render(<Home {...props} />)

    expect(screen.getByRole("heading", { level: 1, name: "ޖާވާބު" })).toHaveClass("cms-visually-hidden")
    expect(screen.getByRole("heading", { name: props.strings.latest })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" })).toHaveAttribute("href", props.posts[0]!.url!)
    expect(screen.getByRole("link", { name: "View all" })).toHaveAttribute("href", props.archive_url!)
  })

  it("shows the front page", () => {
    const props = homePage as unknown as HomeProps

    render(<Home {...props} />)

    expect(screen.getByRole("heading", { level: 1, name: "މަރުޙަބާ" })).toBeInTheDocument()
    expect(screen.getByText("ދިވެހިރާއްޖެ")).toBeInTheDocument()
    expect(screen.getByText(props.strings.empty)).toBeInTheDocument()
  })

  it("renders in the theme with the menu and the sidebar widgets", () => {
    pageProps = home

    render(
      <CmsLayout>
        <Home {...(home as unknown as HomeProps)} />
      </CmsLayout>,
    )

    expect(screen.getByRole("link", { name: "ޖާވާބު" })).toHaveClass("cms-theme__site-name")
    expect(screen.getByText("ޓެގްލައިން")).toBeInTheDocument()

    const main = screen.getByRole("navigation", { name: "Main" })

    expect(within(main).getByRole("link", { name: "ފުރަތަމަ ސަފްހާ" })).toHaveAttribute("href", "/")

    const sidebar = screen.getByRole("complementary", { name: "Sidebar" })

    expect(within(sidebar).getByRole("heading", { name: "ފަހުގެ ޚަބަރު" })).toBeInTheDocument()
    expect(within(sidebar).getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" })).toBeInTheDocument()
    expect(document.querySelector(".cms-theme__body--sidebar")).not.toBeNull()
  })
})

describe("layouts", () => {
  it("leaves pages outside the CMS alone", () => {
    render(
      <CmsLayout>
        <p>ބޭރު</p>
      </CmsLayout>,
    )

    expect(screen.getByText("ބޭރު")).toBeInTheDocument()
    expect(document.querySelector(".cms-admin, .cms-theme")).toBeNull()
  })

  it("gives pages without a layout the CMS's", async () => {
    const Page = () => <p>page</p>
    const WithLayout = Object.assign(() => <p>own</p>, { layout: null })

    const resolve = withCmsLayouts((name: string) => ({ default: name === "own" ? WithLayout : Page }))

    const page = await resolve("post-types::Posts/Index")
    const own = await resolve("own")

    expect(typeof (page.default as unknown as { layout: unknown }).layout).toBe("function")
    expect((own.default as unknown as { layout: unknown }).layout).toBeNull()
  })

  it("the theme is right to left in Dhivehi, with a language switcher", () => {
    render(
      <ThemeLayout
        site={{
          name: "ޖާވާބު",
          tagline: null,
          footer_text: "ފޫޓަރު",
          home_url: "/",
          locale: "dv",
          direction: "rtl",
          locales: [
            { code: "dv", name: "Dhivehi", native_name: "ދިވެހި", direction: "rtl", url: "/?lang=dv", current: true },
            { code: "en", name: "English", native_name: "English", direction: "ltr", url: "/?lang=en", current: false },
          ],
        }}
        widgets={{}}
      >
        <p>ސަފްހާ</p>
      </ThemeLayout>,
    )

    expect(document.querySelector(".cms-theme")).toHaveAttribute("dir", "rtl")
    expect(screen.getByRole("link", { name: "ދިވެހި" })).toHaveAttribute("aria-current", "true")
    expect(screen.getByRole("link", { name: "English" })).toHaveAttribute("hreflang", "en")
    expect(screen.getByText("ފޫޓަރު")).toBeInTheDocument()
    expect(document.querySelector(".cms-theme__body--sidebar")).toBeNull()
  })

  it("the admin layout takes its data as props too", () => {
    render(
      <AdminLayout
        site={{
          name: "ޖާވާބު",
          tagline: null,
          footer_text: null,
          home_url: "/",
          locale: "dv",
          direction: "rtl",
          locales: [],
        }}
        navigation={[
          {
            key: "",
            label: null,
            items: [
              {
                key: "help",
                label: "އެހީ",
                url: null,
                icon: null,
                active: false,
                children: [
                  { key: "docs", label: "ޑޮކިއުމެންޓޭޝަން", url: "/docs", icon: null, active: false, children: [] },
                ],
              },
            ],
          },
        ]}
      >
        <p>ސަފްހާ</p>
      </AdminLayout>,
    )

    expect(document.querySelector(".cms-admin")).toHaveAttribute("dir", "rtl")
    expect(screen.getByText("އެހީ").tagName).toBe("SPAN")
    expect(screen.getByRole("link", { name: "ޑޮކިއުމެންޓޭޝަން" })).toHaveAttribute("href", "/docs")
  })
})
