---
title: The content model
sidebar_position: 2
---

The CMS registers these for you, from `config/cms.php`:

| What | Key | Details |
| --- | --- | --- |
| Post type | `page` | Pages: content, media, author. No archive. URL `/{slug}`, at the site root |
| Post type | `news` | News articles: content, excerpt, media, author, scheduling; categories and tags. URL `/news/{year}/{month}/{slug}`, archive at `/news` |
| Taxonomy | `categories` | Hierarchical |
| Taxonomy | `tags` | Flat |
| Menu location | `header` | Up to 2 levels |
| Menu location | `footer` | 1 level |
| Widget location | `sidebar` | Next to the content in the theme |
| Widget location | `footer` | In the theme's footer |
| Widget type | `recent-posts` | [Widgets](widgets.md) |
| Widget type | `terms` | [Widgets](widgets.md) |

Everything else about posts (statuses, scheduling, meta fields, media, permalinks, admin pages) is [javaabu/post-types](https://github.com/Javaabu/post-types)'.

## Leaving something out

Remove it from the config:

```php
// config/cms.php
'post_types' => ['page'],               // no news
'taxonomies' => ['categories'],         // no tags (remove them from news first: see below)
'menu_locations' => ['header' => 2],    // no footer menu
'widget_locations' => ['sidebar'],
'widgets' => [],                        // only javaabu/widgets' own text, image and link widgets
```

## Changing a default

Entries can be definitions as well as keys. Start from `Javaabu\Cms\Content\Defaults`:

```php
// config/cms.php
use Javaabu\Cms\Content\Defaults;

'post_types' => [
    'page',
    Defaults::news()->taxonomies(['categories'])->permalink('{slug}'),
],
```

Or remove the key from the config and register your own in a service provider:

```php
use Javaabu\Cms\Content\Defaults;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\Stencil\Fields\TextInput;

public function boot(): void
{
    PostTypes::register(
        Defaults::news()->metaFields([TextInput::make('source')->label(__('Source'))->translatable()])
    );
}
```

Menu locations take a maximum depth, and widget locations a label key:

```php
'menu_locations' => ['header' => 3, 'footer' => 1, 'mobile'],
```

A location without a depth uses dynamic-menubuilder's default. Labels for `header`, `footer` and `sidebar` are built in and translated; other keys are labelled from the key (`mobile` → "Mobile").

## Adding post types

Register them with javaabu/post-types as usual. They show up in the admin navigation, the dashboard, the routes, the recent-posts and terms widgets, and as menu sources, without anything else:

```php
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;

PostTypes::register(
    PostType::make('downloads')
        ->labels('Download', 'Downloads')
        ->icon('download')
        ->supports(['content', 'media'])
        ->taxonomies(['categories'])
        ->media([MediaPickerField::make('files')->multiple()])
);
```

Add the labels to `lang/dv.json`, then `php artisan permissions:sync`.

Keys are permanent: they're stored on every post and name the routes and permissions.
