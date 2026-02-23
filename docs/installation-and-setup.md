---
title: Installation & Setup
sidebar_position: 1.2
---

This guide will walk you through installing and setting up the Javaabu CMS package in your Laravel application.

## Installation

### Step 1: Install via Composer

Install the package via composer:

```bash
composer require javaabu/cms
```

### Step 2: Run Setup Command

The easiest way to set up the CMS is using the setup command:

```bash
php artisan cms:setup
```

This command will:
- Publish the configuration file
- Publish and run migrations
- Optionally install default post types and categories
- Seed CMS permissions
- Display next steps

#### Setup Command Options

```bash
# Install with default post types and categories
php artisan cms:setup --with-defaults

# Skip migrations
php artisan cms:setup --skip-migrations

# Skip permission seeding
php artisan cms:setup --skip-permissions

# Force in production
php artisan cms:setup --force

# Combine options
php artisan cms:setup --with-defaults --force
```

#### Default Post Types and Categories

When using the `--with-defaults` option (or when prompted during setup), the following will be installed:

**Post Types:**
- News
- Blog Posts
- Downloads
- Announcements
- Publications
- Jobs
- Galleries
- Tenders
- Reports
- Pages

**Category Types:**
- News Categories
- Blog Categories
- Download Categories
- Announcement Categories
- Publication Categories
- Job Categories
- Gallery Categories
- Tender Categories
- Report Categories

**Sample Categories:**
- News: General, Press Releases
- Blog: Technology, Business
- Jobs: Full Time, Part Time, Intern

:::tip
You can customize these defaults in `config/cms.php` under `default_category_types`, `default_post_types`, and `default_categories` before running the setup command.
:::

### Step 3: Register Routes

Add CMS routes to your `routes/web.php`:

```php
use Javaabu\Cms\Support\Routes;

// Admin routes (protected by auth middleware)
Routes::admin(
    prefix: 'admin',
    middleware: ['web', 'auth', 'verified']
);

// Public routes (for displaying posts)
Routes::web(
    prefix: null,  // or 'blog', 'news', etc.
    middleware: ['web']
);
```

#### Custom Route Configuration

You can customize routes per post type:

```php
use Javaabu\Cms\Support\Routes;

// Register routes for a specific post type
Routes::customPostType(
    postTypeSlug: 'news',
    prefix: 'news',
    middleware: ['web'],
    controller: '\App\Http\Controllers\NewsController'  // Optional custom controller
);
```

### Step 4: Admin Routes

Add CMS routes to your admin routes file (typically `routes/admin.php`):

```php
use Javaabu\Cms\Http\Controllers\Admin\MediaController;

// Media routes
Route::match(['PUT', 'PATCH'], 'media', [MediaController::class, 'bulk'])->name('media.bulk');
Route::get('media/picker', [MediaController::class, 'picker'])->name('media.picker');
Route::resource('media', MediaController::class, [
    'parameters' => [
        'media' => 'media',
    ],
]);
```

## Configuration

### Publishing Config File

The config file is automatically published during setup, but you can republish it:

```bash
php artisan vendor:publish --tag=cms-config
```

### Config File Structure

The configuration file (`config/cms.php`) contains:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    */
    'post_types' => [
        'news' => [
            'label' => 'News',
            'singular_label' => 'News Article',
            'features' => ['categories', 'featured_image', 'excerpt'],
            'category_types' => ['news-categories'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Types
    |--------------------------------------------------------------------------
    */
    'category_types' => [
        'news-categories' => [
            'label' => 'News Categories',
            'singular_label' => 'News Category',
            'hierarchical' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Post Type Features
    |--------------------------------------------------------------------------
    */
    'default_features' => [
        'title',
        'content',
        'excerpt',
        'featured_image',
        'categories',
        'publish_date',
        'status',
        'slug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    */
    'media' => [
        'featured_image_sizes' => [
            'thumb' => [300, 300],
            'medium' => [600, 400],
            'large' => [1200, 800],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 15,
    ],
];
```

## Database Migrations

### Running Migrations

If you skipped migrations during setup, run them manually:

```bash
php artisan migrate
```

### Publishing Migrations

To customize migrations, publish them first:

```bash
php artisan vendor:publish --tag=cms-migrations
```

This creates the following tables:
- `category_types`
- `categories`
- `post_types`
- `posts`
- `category_model` (pivot table)

## Permissions Setup

### Seeding Permissions

If you skipped permissions during setup, seed them manually:

```php
use Javaabu\Cms\seeders\CmsPermissionsSeeder;

CmsPermissionsSeeder::seedPermissions();
```

Or add to your `DatabaseSeeder`:

```php
public function run()
{
    // Other seeders...
    
    \Javaabu\Cms\seeders\CmsPermissionsSeeder::seedPermissions();
}
```

### Available Permissions

The package dynamically creates permissions for each Post Type and Category Type you create. Permissions are based on the slug of the type.

**Post Type Permissions** (11 per type):

For each Post Type (e.g., 'news'), the following permissions are created:
- `edit_{slug}` - Edit own posts (e.g., `edit_news`)
- `edit_others_{slug}` - Edit all posts
- `delete_{slug}` - Delete own posts
- `delete_others_{slug}` - Delete all posts
- `view_{slug}` - View own posts
- `view_others_{slug}` - View all posts
- `force_delete_{slug}` - Force delete own posts
- `force_delete_others_{slug}` - Force delete all posts
- `publish_{slug}` - Publish own posts
- `publish_others_{slug}` - Publish all posts
- `import_{slug}` - Import posts

**Category Type Permissions** (4 per type):

For each Category Type (e.g., 'news-categories'), the following permissions are created:
- `edit_{slug}` - Edit categories (e.g., `edit_news_categories`)
- `delete_{slug}` - Delete categories
- `view_{slug}` - View categories
- `import_{slug}` - Import categories

:::tip
Call `CmsPermissionsSeeder::seedPermissions()` after creating your Post Types and Category Types to generate the appropriate permissions.
:::

## Frontend Assets (Optional)

### Editor.js Setup

If using Editor.js for content editing:

```bash
npm install --save @editorjs/editorjs @editorjs/header @editorjs/list @editorjs/image @editorjs/quote @editorjs/table @editorjs/delimiter @editorjs/embed @editorjs/link @editorjs/raw @editorjs/simple-image @calumk/editorjs-columns
```

Or copy from package.json:

```json
{
  "dependencies": {
    "@calumk/editorjs-columns": "^0.3.2",
    "@editorjs/delimiter": "^1.4.2",
    "@editorjs/editorjs": "^2.29.1",
    "@editorjs/embed": "^2.7.4",
    "@editorjs/header": "^2.6.0",
    "@editorjs/image": "^2.6.0",
    "@editorjs/link": "^2.3.1",
    "@editorjs/list": "^1.6.1",
    "@editorjs/quote": "^2.4.0",
    "@editorjs/raw": "^2.2.0",
    "@editorjs/simple-image": "^1.4.0",
    "@editorjs/table": "^1.3.0"
  }
}
```

### Laravel Object Configuration

To enable correct functionality for the Media Picker and other CMS features, add the following script block to your admin layout's `<head>`:

```blade
<script>
    window.Laravel = <?php echo json_encode([
        'csrfToken'        => csrf_token(),
        'mediaPicker'      => translate_route('admin.media.picker'),
        'locale'           => app()->getLocale(),
        'admin_domain'     => url(''),
        'public_domain'    => config('app.url')
    ]); ?>
</script>
```

### SASS Setup

To include the Media Library styles, import the SASS file in your `admin.scss`:

```scss
@import 'inc/media-library';
@import 'inc/fileinput-overrides';
```

## Verification

After installation, verify everything is working:

1. **Check routes**: `php artisan route:list | grep cms`
2. **Check migrations**: Verify tables exist in database
3. **Access admin panel**: Visit `/admin/category-types` (requires authentication)
4. **Check permissions**: Verify permissions exist in your permissions table

## Next Steps

- [Configure Post Types](basic-usage/creating-post-types.md)
- [Create Categories](basic-usage/managing-categories.md)
- [Create Your First Post](basic-usage/creating-posts.md)
- [Customize Views](basic-usage/customizing-views.md)
