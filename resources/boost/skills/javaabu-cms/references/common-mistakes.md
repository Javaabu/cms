# Common mistakes with javaabu/cms

- **Calling `Route::cms()` before the app's own routes.** Pages are `/{slug}`, which catches any one-segment URL. Put it last.
- **Registering a package's admin routes twice.** `Route::cmsAdmin()` already calls `postTypesAdmin()`, `menus()`, `widgets()`, `settings()`, `mediapicker()` and `roles()`. Turn one off (`'roles' => false`) instead of registering it again under the same names.
- **Writing a `Post`/`Page`/`Category` model, migration or controller.** Use javaabu/post-types' post types and taxonomies; add fields with `->metaFields()`.
- **Changing the news post type by registering `news` before the CMS does.** The CMS registers its defaults in `boot()`; remove `news` from `config('cms.post_types')` (or replace it with `Defaults::news()->…`) and register yours.
- **Setting `post-types.views` or `widgets.layout` while `cms.sync_packages` is on.** The CMS overwrites them from `config('cms')`. Set `cms.views`/`cms.layouts`, or turn `sync_packages` off.
- **`settings('site_name')`.** The CMS's settings are `cms_site_name`, `cms_tagline`, `cms_front_page`, `cms_home_post_type`, `cms_footer_text`; read them with `Cms::siteName()` and friends.
- **Navigation links with full route names.** `NavigationItem::route('reports.index')` is relative to the admin name prefix (`admin.`). Pass `relative: false` for a full name.
- **Checking `auth()->user()` in the admin.** Each `Route::cmsAdmin()` call has its guard; use `$request->user(CmsRoutes::guard($request))` or policies with `Model $user`.
- **Forgetting `permissions:sync` after adding a post type**: its admin pages and navigation link stay hidden.
- **Inertia pages without a layout.** Resolve pages with `withCmsLayouts()` so post-types', widgets' and menubuilder's pages get the admin layout and theme.
- **Importing v1 before migrating.** `cms:import-v1` reads the `cms_v1_*` tables that `php artisan migrate` creates by renaming v1's.
- **Physical CSS in overridden views** (`ml-4`, `text-left`): use logical utilities so Dhivehi pages work.
