---
title: Installation & Setup
sidebar_position: 1.2
---

You can install the package via composer:

```bash
composer require javaabu/cms
```

# Publishing the config file

Publishing the config file is optional:

```bash
php artisan vendor:publish --provider="Javaabu\Cms\CmsServiceProvider" --tag="cms-config"
```

This is the default content of the config file:

```php
// TODO
```
