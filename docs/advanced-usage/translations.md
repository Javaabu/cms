---
title: Translations
sidebar_position: 4
---

Translations are optional. Install [javaabu/translatable](https://github.com/Javaabu/translatable) and add languages, and:

- pages, news, categories and tags get a title, excerpt and content per language (javaabu/post-types);
- menu items get a title and URL per language (javaabu/dynamic-menubuilder);
- widget settings marked translatable, such as the titles of the recent-posts and terms widgets, get a value per language (javaabu/widgets);
- the site name, tagline and footer text get a value per language;
- the theme shows a language switcher (`?lang=dv`), and is right to left in Dhivehi;
- `cms:install` creates its starter content in English and Dhivehi.

```bash
composer require javaabu/translatable
php artisan migrate
```

Add languages as javaabu/translatable describes (`en` and `dv`, for example). `Route::cms()` adds its `language` middleware to the public routes.

## Turning it off

```php
// config/cms.php
'translatable' => false,
```

This turns off the CMS's own parts (the switcher, translatable site settings). The packages' own translation support follows their own config.

## In code

```php
use Javaabu\Cms\Support\Translations;

Translations::enabled();        // installed, cms.translatable on, and languages set up
Translations::currentLocale();  // 'dv'
Translations::direction();      // 'rtl'
Translations::switcher();       // [['code' => 'dv', 'native_name' => 'ދިވެހި', 'url' => …, 'current' => true], …]
```

Never reference javaabu/translatable's classes directly in code that has to work without it; go through `Translations`.
