---
title: Requirements
sidebar_position: 1.1
---

This package requires the following:

## Server Requirements

- **PHP**: ^8.2 or higher
- **Laravel**: ^11.0 or ^12.0
- **Database**: MySQL 5.7+ / PostgreSQL 9.6+ / SQLite 3.8+

## PHP Extensions

The following PHP extensions are required:

- PDO PHP Extension
- Mbstring PHP Extension
- JSON PHP Extension
- OpenSSL PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- Fileinfo PHP Extension

## Package Dependencies

This package automatically installs the following dependencies:

### Required Packages

- **kalnoy/nestedset** (v6.0.5): For hierarchical category support
- **naxeem/thaana-transliterator** (^1.0): For Dhivehi transliteration
- **anhskohbo/no-captcha** (^3.0): For reCAPTCHA support
- **mariuzzo/laravel-js-localization** (dev-l12-compatibility): For JavaScript translations
- **predis/predis** (^2.2): For Redis cache support
- **diglactic/laravel-breadcrumbs** (^10.0): For breadcrumb navigation
- **javaabu/menus** (*): For menu management

### Frontend Dependencies (Optional)

If you plan to use Editor.js for content editing, install these npm packages:

```bash
npm install --save @editorjs/editorjs@^2.29.1
npm install --save @editorjs/header@^2.6.0
npm install --save @editorjs/list@^1.6.1
npm install --save @editorjs/image@^2.6.0
npm install --save @editorjs/quote@^2.4.0
npm install --save @editorjs/table@^1.3.0
npm install --save @editorjs/delimiter@^1.4.2
npm install --save @editorjs/embed@^2.7.4
npm install --save @editorjs/link@^2.3.1
npm install --save @editorjs/raw@^2.2.0
npm install --save @editorjs/simple-image@^1.4.0
npm install --save @calumk/editorjs-columns@^0.3.2
```

## Recommended

- A basic understanding of Laravel framework
- Familiarity with Blade templating
- Knowledge of Laravel policies and authorization
- Understanding of Laravel migrations and Eloquent ORM
