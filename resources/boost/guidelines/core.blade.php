{{--
    javaabu/cms guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

@verbatim
## javaabu/cms

- The CMS is assembled from javaabu/post-types (pages, news, categories, tags), javaabu/dynamic-menubuilder (header and footer menus), javaabu/widgets (sidebar and footer widgets) and javaabu/settings (the Site page). Never write models, migrations, controllers or CRUD views for pages, posts, categories, menus or widgets; add post types with `PostTypes::register()` (start from `Javaabu\Cms\Content\Defaults::news()`) and change the defaults in `config/cms.php`.
- Routes: `Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.'])` registers the dashboard and every package's admin pages; `Route::cms()` registers the home page and the public pages, and goes after the app's own routes because pages live at `/{slug}`.
- Admin links go through `Cms::navigation()->add(NavigationItem::make('reports')->label(fn () => __('Reports'))->route('reports.index')->group('content')->can('view_reports'))`. Site details come from `Cms::siteName()`, `Cms::tagline()`, `Cms::frontPage()` and `Cms::site()`.
- Public Blade pages extend `cms::layouts.public` (section `content`); Inertia apps resolve pages with `withCmsLayouts()` from `@javaabu/cms`.
- Permissions: `view_dashboard` plus each package's (`view_news`, `edit_pages`, `view_menus`, `edit_site_settings`, …); run `php artisan permissions:sync`. v1 sites: `php artisan migrate` then `php artisan cms:import-v1`.
- Always activate the `javaabu-cms` skill when setting up the CMS, changing its content model, routes, navigation, dashboard, theme or site settings, or upgrading from v1.
@endverbatim
