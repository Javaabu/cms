# CMS

[![Tests](https://github.com/Javaabu/cms/actions/workflows/tests.yml/badge.svg?branch=next-gen)](https://github.com/Javaabu/cms/actions/workflows/tests.yml?query=branch%3Anext-gen)
[![codecov](https://codecov.io/gh/Javaabu/cms/branch/next-gen/graph/badge.svg)](https://codecov.io/gh/Javaabu/cms)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/cms.svg)](https://packagist.org/packages/javaabu/cms)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

A complete CMS for Laravel, assembled from Javaabu packages. Install it, add two route macros, and you have an admin with a dashboard and a public site with a theme.

- **Content:** pages at the site root and news with categories and tags, from [javaabu/post-types](https://github.com/Javaabu/post-types), with block-editor content, media and scheduling.
- **Menus and widgets:** header and footer menus from [javaabu/dynamic-menubuilder](https://github.com/Javaabu/dynamic-menubuilder), sidebar and footer widgets from [javaabu/widgets](https://github.com/Javaabu/widgets), plus recent-posts and categories widgets.
- **Admin:** a dashboard, navigation that follows each user's permissions, the media library, site settings and roles, under one prefix and guard.
- **Public theme:** a Blade layout and React layouts you can override, right to left in Dhivehi.
- **Install command:** `php artisan cms:install` publishes the config, migrates, syncs permissions and creates starter content in English and Dhivehi.
- **Both stacks:** Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or Inertia React (@javaabu/forms and Onu), with one config option.
- **Optional translations:** with [javaabu/translatable](https://github.com/Javaabu/translatable), content, menus, widgets and settings are translatable and the theme has a language switcher.

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::cmsAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
    Route::cms(); // last: pages live at /{slug}
});
```

Upgrading from v1? See [docs/upgrading.md](docs/upgrading.md): v2 brings v1's content over with `php artisan cms:import-v1`.

## Documentation

You'll find the documentation on [https://docs.javaabu.com/docs/cms](https://docs.javaabu.com/docs/cms), and in [docs/](docs/intro.md).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving this package? Feel free to create an issue on GitHub; we'll try to address it as soon as possible.

If you've found a bug regarding security, please mail [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
