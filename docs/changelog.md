---
title: Changelog
sidebar_position: 1.5
---

All notable changes to this package are documented here and on [GitHub](https://github.com/Javaabu/cms/blob/main/CHANGELOG.md).

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - Initial Release

### Added

#### Core Features
- Custom post types with configurable features
- Hierarchical categories with nested set support
- Complete CRUD operations for posts, post types, categories, and category types
- Built-in permission system with policies

#### Models
- `Post` - Main content model with soft deletes
- `PostType` - Define custom content types
- `Category` - Hierarchical category support
- `CategoryType` - Category type definitions

#### Controllers
- Admin controllers for CategoryTypes, Categories, and Posts
- Full CRUD operations with filtering and search
- Bulk actions support
- Trash/restore functionality for posts

#### Views
- Complete admin interface (Blade templates)
- Category management views
- Post management views
- Category type management views
- Filter forms and search functionality

#### Features
- Post statuses (draft, published, scheduled, pending, rejected, archived)
- Publishing and scheduling posts
- Category assignment
- Featured images
- Post excerpts
- Document attachments
- Image galleries
- Video links
- Expiration dates
- Custom page styles
- Reference numbers
- Recently updated flags

#### Configuration
- Flexible post type configuration
- Category type configuration
- Media settings
- Pagination settings
- Default feature sets

#### Routes
- Helper class for registering admin and public routes
- Custom post type route registration
- Category filtering routes

#### Database
- Migrations for all CMS tables
- Support for MySQL, PostgreSQL, and SQLite

#### Commands
- `cms:setup` - Quick installation and setup command

#### Seeders
- `CmsPermissionsSeeder` - Seed CMS permissions

#### Enums
- `PostStatus` - Post status enum with helper methods
- `PostTypeFeatures` - Available post type features

#### Policies
- `CategoryTypePolicy` - Authorization for category types
- `CategoryPolicy` - Authorization for categories
- `PostTypePolicy` - Authorization for post types
- `PostPolicy` - Authorization for posts

#### Documentation
- Comprehensive documentation with examples
- Installation and setup guide
- Requirements documentation
- Basic usage guides
- API reference

### Dependencies
- Laravel 11.0+ / 12.0+
- PHP 8.2+
- kalnoy/nestedset v6.0.5
- naxeem/thaana-transliterator ^1.0
- anhskohbo/no-captcha ^3.0
- mariuzzo/laravel-js-localization dev-l12-compatibility
- predis/predis ^2.2
- diglactic/laravel-breadcrumbs ^10.0
- javaabu/menus *

### Editor.js Support
- Support for Editor.js content editing
- Compatible with Editor.js plugins:
  - @editorjs/editorjs ^2.29.1
  - @editorjs/header ^2.6.0
  - @editorjs/list ^1.6.1
  - @editorjs/image ^2.6.0
  - @editorjs/quote ^2.4.0
  - @editorjs/table ^1.3.0
  - @editorjs/delimiter ^1.4.2
  - @editorjs/embed ^2.7.4
  - @editorjs/link ^2.3.1
  - @editorjs/raw ^2.2.0
  - @editorjs/simple-image ^1.4.0
  - @calumk/editorjs-columns ^0.3.2

---

For detailed changes in future versions, see the [GitHub releases](https://github.com/Javaabu/cms/releases).
