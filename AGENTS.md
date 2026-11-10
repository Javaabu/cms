# cms: notes for AI coding agents

`javaabu/cms` v2 is a Laravel package that assembles a complete CMS from finished Javaabu packages: javaabu/post-types (pages, news, taxonomies), javaabu/widgets, javaabu/dynamic-menubuilder, javaabu/settings v2, javaabu/mediapicker, javaabu/block-editor, javaabu/permissions v3, with javaabu/forms, javaabu/stencil and javaabu/manifest for views. It owns very little storage: the content model, the route macros, the admin navigation and dashboard, the theme, the install command and the v1 importer. inertiajs/inertia-laravel and javaabu/translatable are optional.

Treat `Cms`/`CmsManager`, `Navigation`/`NavigationItem`, `Content\Defaults`, `Route::cmsAdmin()`, `Route::cms()`, the controllers' `data()` methods, `DashboardPolicy`, `view_dashboard`, the setting keys (`cms_*`), the widget keys, the page props, the shared props and the `@javaabu/cms` exports as public contracts.

## Layout

| Path | What |
| --- | --- |
| `src/CmsServiceProvider.php` | Config sync to the other packages, the default content model, settings page, macros, permissions, commands |
| `src/CmsManager.php`, `src/Facades/Cms.php` | Navigation, site details, shared data, views and layouts |
| `src/Content` | `Defaults` (default post types and taxonomies), `StarterContent` (cms:install's content) |
| `src/Navigation` | `Navigation`, `NavigationItem`, `DefaultNavigation` |
| `src/Routing/CmsRoutes.php` | Both macros, the admin name prefix per guard, the home URL |
| `src/Http` | `DashboardController`, `HomeController`, `ShareCmsData` (Inertia shared props) |
| `src/Dashboard`, `src/Policies` | The dashboard's data and policy subject; `DashboardPolicy` |
| `src/Settings/SiteSettings.php`, `src/Widgets` | The Site settings page; recent-posts and terms widgets |
| `src/Upgrade` | `V1Tables` (the rename), `V1Importer`, `EditorJsConverter` |
| `src/Console` | `cms:install`, `cms:import-v1` |
| `src/Support/Translations.php` | Optional javaabu/translatable, feature-detected |
| `database/migrations` | Only the v1 table rename (dated before post-types' migration) |
| `resources/views` | Blade layouts, dashboard, home, components, widgets |
| `resources/js` | TypeScript: layouts, pages (`Pages/Admin/Dashboard`, `Pages/Public/Home`), widgets, `withCmsLayouts` |
| `resources/css/cms.css`, `dist/` | Styles (logical properties only) and their prebuilt copy, **committed** |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `tests/js/fixtures` | Inertia props written by `InertiaPagesTest` |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Laravel Boost guideline and skill |

## Rules

- Don't reimplement what a dependency does. If one lacks something, open a `cross-package` issue in its repo instead of working around it here.
- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…` or a sentence, early returns. Pint enforces formatting.
- Nothing may assume the `web` guard or `App\Models\User`; HTTP tests use two guards (`web` → `User`, `web_admin` → `Admin`).
- Query posts through `PostTypes::postModel()` / a post type's `query()`, never a hard-coded class.
- When page or shared props change, run `CMS_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter InertiaPagesTest`, commit `tests/js/fixtures`, and keep `resources/js/types.ts` in step.
- After changing `resources/css`, run `pnpm build` and commit `dist/`.
- Inertia and javaabu/translatable are optional: feature-detect them, keep test classes loadable without them. CI has a leg without both.
- Every user-facing string goes through `__()` with an English key, in `lang/en.json` and `lang/dv.json` (`LangTest`). Logical CSS only (`RtlTest`). Thaana in tests of user-facing behaviour; uncertain Dhivehi goes in `docs/advanced-usage/dhivehi-and-rtl.md`.
- Every change needs tests, docs (linked from `llms.txt`, checked by `DocsTest`), the Boost skill if usage changes, and a line in `CHANGELOG.md`.
- Work on the `next-gen` branch. Never commit to `main`, tag or release.

## Commands

```bash
composer install && pnpm install      # composer first: the javaabu React code is in vendor/
composer lint && composer test        # Pint and PHPUnit
pnpm lint && pnpm format:check && pnpm typecheck && pnpm test
pnpm build                            # dist/
```
