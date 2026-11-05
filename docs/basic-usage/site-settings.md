---
title: Site settings
sidebar_position: 5
---

The CMS adds a **Site** page to [javaabu/settings](https://github.com/Javaabu/settings) at `admin/settings/site`:

| Setting | Key | Default |
| --- | --- | --- |
| Site name | `cms_site_name` | `config('app.name')` |
| Tagline | `cms_tagline` | none |
| Page shown on the home page | `cms_front_page` | none: the home page shows the latest posts |
| Latest posts from | `cms_home_post_type` | `config('cms.home.post_type')` |
| Footer text | `cms_footer_text` | none: "© {year} {site}" |

The name, tagline and footer text are translatable when javaabu/translatable is installed (and `cms.translatable` is on).

Editing the page needs `edit_site_settings`; seeing the settings needs `view_settings`.

Read them through the facade, which falls back to sensible values:

```php
use Javaabu\Cms\Facades\Cms;

Cms::siteName();      // the setting, or the app name
Cms::tagline();       // ?string
Cms::footerText();    // ?string
Cms::frontPage();     // the published Page, or null
Cms::homePostType();  // the PostType the home page lists, or null
```

or with javaabu/settings directly: `settings('cms_site_name')`, `Settings::set(['cms_tagline' => 'ޓެގްލައިން'])`.

Turn the page off with `'settings' => false` in `config/cms.php`; the facade then returns the app name and nulls.
