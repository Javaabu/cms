---
title: Widgets
sidebar_position: 6
---

Admins manage widgets at `admin/widgets` ([javaabu/widgets](https://github.com/Javaabu/widgets)). The theme shows the `sidebar` and `footer` locations. Besides javaabu/widgets' Text, Image and Link widgets, the CMS adds two.

## Recent posts (`recent-posts`)

The latest published posts of a post type, with a link to its archive.

| Setting | |
| --- | --- |
| `title` | Translatable |
| `post_type` | A public post type (default: `cms.home.post_type`) |
| `count` | 1 to 20 (default 5) |
| `show_date` | Default on |

Data: `posts` (`id`, `title`, `url`, `date`, `date_label`), `archive_url`, `archive_label`.

## Categories and tags (`terms`)

The terms of a taxonomy, each linking to its archive on a post type.

| Setting | |
| --- | --- |
| `title` | Translatable |
| `source` | `{post type}:{taxonomy}`, such as `news:categories` |
| `show_counts` | How many published posts each has |
| `hide_empty` | Leave out terms without published posts (default on) |

Data: `terms` (`id`, `name`, `url`, `count`, `parent_id`), `show_counts`.

## Rendering

The theme renders them for you. Anywhere else:

```blade
<x-widgets::location name="sidebar" class="cms-widgets" />
```

```tsx
import { cmsWidgets } from "@javaabu/cms"
import { WidgetLocation } from "@javaabu/widgets"

<WidgetLocation name="sidebar" widgets={widgets.sidebar} components={cmsWidgets} />
```

or call `registerCmsWidgets()` once (in `app.tsx`) and leave out `components`.

The Blade views are `cms::widgets.recent-posts` and `cms::widgets.terms` (publish them to change them); the React components are `RecentPosts` and `Terms` from `@javaabu/cms`.

## Your own widgets

Write them as javaabu/widgets describes and register them, in a service provider or in `config('cms.widgets')`:

```php
'widgets' => [
    Javaabu\Cms\Widgets\RecentPostsWidget::class,
    Javaabu\Cms\Widgets\TermsWidget::class,
    App\Widgets\NewsletterWidget::class,
],
```

Give React the component with `<ThemeLayout components={{ Newsletter }}>` or `registerWidgets({ Newsletter })`.
